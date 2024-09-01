<?php

declare(strict_types=1);

namespace Psi\S3EventSns\Services;

use Aws\S3\S3Client;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psi\S3EventSns\Enums\EntityType;

class AwsS3Service
{
    private S3Client $s3;

    private Encrypter $encrypter;

    public function __construct(
        private readonly string $region,
        private readonly string $encryptKey,
        private readonly string $disk

    ) {
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region' => $this->region,
        ]);
        $this->encrypter = new Encrypter(
            key: $this->encryptKey,
            cipher: 'AES-128-CBC'
        );
    }

    /**
     * Get the tags from an S3 object. Returns an array of key/value pairs.
     * The Laravel Storage driver doesn't support this, so we need to use the SDK directly.
     */
    public function getTags(string $bucket, string $key): array
    {
        /** @var array $tagSet */
        $tagSet = $this->s3->getObjectTagging([
            'Bucket' => $bucket,
            'Key' => $key,
        ])->get('TagSet');

        return collect($tagSet)->mapWithKeys(function (array $tag) {
            return [$tag['Key'] => $tag['Value']];
        })->toArray();
    }

    public function getContents(string $key, bool $encrypted = false): array
    {
        $json = Storage::disk($this->disk)->get($key);

        if ($encrypted) {
            $json = $this->decrypt(contents: $json);
        }

        return \json_decode(json: $json, associative: true) ?? [];
    }

    /**
     * Store an Entity on S3 creating a key based on the entity type.
     */
    public function storeEntity(EntityType $entity, mixed $contents, ?string $uuid = null, array $tags = []): string
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
        Storage::disk($this->disk)
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
