<?php

declare(strict_types=1);

namespace Psi\S3EventSns\Services;

use Exception;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psi\S3EventSns\Enums\EntityType;

class AwsS3Service
{
    private Encrypter $encrypter;

    /** @var array|string[] */
    private array $disks;

    public function __construct(
        private readonly string $encryptKey,
        private readonly string $disk

    ) {

        $this->encrypter = new Encrypter(
            key: $this->encryptKey,
            cipher: 'AES-128-CBC'
        );
        $this->disks = \explode(',', $this->disk);
    }

    /**
     * Get the tags from an S3 object. Returns an array of key/value pairs.
     * The Laravel Storage driver doesn't support this, so we need to use the SDK directly.
     *
     * @throws Exception
     */
    public function getTags(string $bucket, string $key): array
    {
        $disk = $this->getDisk($bucket);

        $storageClient = Storage::disk($disk)->getClient(); // @phpstan-ignore-line

        /** @var array $tagSet */
        $tagSet = $storageClient->getObjectTagging([
            'Bucket' => $bucket,
            'Key' => $key,
        ])->get('TagSet');

        return collect($tagSet)->mapWithKeys(function (array $tag) {
            return [$tag['Key'] => $tag['Value']];
        })->toArray();
    }

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
}
