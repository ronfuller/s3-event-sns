<?php

declare(strict_types=1);

namespace Psi\S3EventSns\Services;

use Aws\S3\S3Client;
use Exception;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Psi\S3EventSns\Enums\EntityType;

class AwsS3Service
{
    use Conditionable;

    private Encrypter $encrypter;

    private array $tags;

    /** @var array|string[] */
    private array $disks;

    public bool $logging;

    public function __construct(
        private readonly string $encryptKey,
        private readonly string $disk

    ) {

        $this->encrypter = new Encrypter(
            key: $this->encryptKey,
            cipher: 'AES-128-CBC'
        );
        $this->disks = \explode(',', $this->disk);

        $this->logging = Config::boolean('s3-event-sns.logging');
    }

    /**
     * Get the tags from an S3 object. Returns an array of key/value pairs.
     * The Laravel Storage driver doesn't support this, so we need to use the SDK directly.
     *
     * @throws Exception
     */
    public function getTags(string $bucket, string $key): array
    {
        // If we can't get tags from the S3 client, we'll get an empty array
        $tags = $this->getClientTags(bucket: $bucket, key: $key);

        // If we failed to get S3 client tags (PROD environment on Laravel Forge Weirdness) , infer from the key and bucket
        $tags = empty($tags) ? $this->getInferredTags(bucket: $bucket, key: $key) : $tags;

        $this->when(
            value: $this->logging,
            callback: fn () => logger()->info('AwsS3Service:S3 Tags', context: [
                'bucket' => $bucket,
                'key' => $key,
                'tags' => $tags,
            ])
        );

        return $tags;

    }

    public function getInferredTags(string $bucket, string $key): array
    {
        $this->when(
            value: $this->logging,
            callback: fn () => logger()->info('AwsS3Service:Get Inferred Tags', context: [
                'bucket' => $bucket,
                'key' => $key,
            ])
        );

        return $this->when(
            value: $this->isArchiveOrder(bucket: $bucket, key: $key),
            callback: fn () => $this->setServiceTags([
                'encrypted' => 'true',
                'version' => '1.0.0',
                'action' => 'archive',
                'entity' => 'order',
            ])
        )->when(
            value: $this->isOrder(bucket: $bucket, key: $key),
            callback: fn () => $this->setServiceTags([
                'encrypted' => 'true',
                'version' => '1.0.0',
                'entity' => 'order',
            ])
        )->when(
            value: $this->isAccount(bucket: $bucket, key: $key),
            callback: fn () => $this->setServiceTags([
                'encrypted' => 'true',
                'version' => '1.0.0',
                'entity' => 'account',
            ])
        )->getServiceTags();
    }

    public function getClientTags(string $bucket, string $key): array
    {
        try {
            $disk = $this->getDisk($bucket);

            /** @var S3Client $storageClient */
            $storageClient = Storage::disk($disk)->getClient(); // @phpstan-ignore-line

            /** @var array $tagSet */
            $tagSet = $storageClient->getObjectTagging([
                'Bucket' => $bucket,
                'Key' => $key,
            ])->get('TagSet');

            $tags = collect($tagSet)->mapWithKeys(function (array $tag) {
                return [$tag['Key'] => $tag['Value']];
            })->toArray();

            $this->when(
                value: $this->logging,
                callback: fn () => logger()->info('AwsS3Service:Get Client Tags', context: [
                    'bucket' => $bucket,
                    'key' => $key,
                    'tags' => $tags,
                ])
            );

            return $tags;

        } catch (\Throwable $th) {
            $this->when(
                value: $this->logging,
                callback: fn () => logger()->error('Error in getting S3 Client tags', context: [
                    'message' => $th->getMessage(),
                ])
            );

            return [];
        }

    }

    /**
     * @throws Exception
     */

    /**
     * @throws Exception
     */
    public function getContents(string $bucket, string $key, bool $encrypted = false): array
    {
        $disk = $this->getDisk($bucket);

        $json = Storage::disk($disk)->get($key);

        if ($encrypted) {
            $json = $this->decrypt(contents: $json);
        }

        return \json_decode(json: $json, associative: true) ?? [];
    }

    /**
     * Store an Entity on S3 creating a key based on the entity type.
     */
    public function storeEntity(EntityType $entity, mixed $contents, ?string $uuid = null, array $tags = [], ?string $disk = null): string
    {
        $tags = $entity->tags(attributes: $tags);
        $key = $this->getKey(entity: $entity, uuid: $uuid);
        $json = \json_encode($contents);

        if ($tags['encrypted'] === 'true') {
            $json = $this->encrypt(contents: $json);
        }
        $options = [
            'Tagging' => $this->urlEncode(data: $tags),
            'visibility' => 'private',
        ];
        $disk = $disk ?? $this->disks[0];

        Storage::disk($disk)
            ->put(
                path: $key,
                contents: $json,
                options: $options
            );

        return $key;
    }

    /**
     * Key is derived from environment and entity type. The S3 notification is tied to the environment prefix.
     */
    private function getKey(EntityType $entity, ?string $uuid = null): string
    {
        return collect([app()->environment(), $entity->value, ($uuid ?? (string) Str::uuid()).'.json'])->join('/');
    }

    private function getDisk(string $bucket): string
    {
        /** @var Collection<int,string> $diskColl */
        $diskColl = collect($this->disks);

        // @phpstan-ignore-next-line
        $disk = $diskColl->first(function ($disk) use ($bucket) {
            $fileSystemDisk = config("filesystems.disks.{$disk}");
            if (\is_null($fileSystemDisk)) {
                throw new \Exception("Disk {$disk} not found in filesystems config.");
            }

            return $fileSystemDisk['bucket'] === $bucket;
        });

        if (\is_null($disk)) {
            throw new \Exception("Disk not found for bucket {$bucket}.");
        }

        return $disk;
    }

    /**
     * AWS S3 Tagging requires url encoded data.
     */
    private function urlEncode(array $data): string
    {
        $encoded = '';
        foreach ($data as $name => $value) {
            $encoded .= urlencode($name).'='.urlencode($value).'&';
        }

        return substr($encoded, 0, -1);
    }

    private function encrypt(string $contents): string
    {
        return $this->encrypter->encrypt(value: $contents);
    }

    private function decrypt(string $contents): string
    {
        return $this->encrypter->decrypt(payload: $contents);
    }

    protected function isArchiveOrder(string $bucket, string $key): bool
    {
        return str_contains($key, 'orders') && str_contains($bucket, 'archive');
    }

    protected function isOrder(string $bucket, string $key): bool
    {
        return str_contains($key, 'orders') && str_contains($bucket, 'entity');
    }

    protected function isAccount(string $bucket, string $key): bool
    {
        return str_contains($key, 'accounts') && str_contains($bucket, 'entity');
    }

    protected function setServiceTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    protected function getServiceTags(): array
    {
        return $this->tags;
    }
}
