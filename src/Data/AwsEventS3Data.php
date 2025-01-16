<?php

namespace Psi\S3EventSns\Data;

use Illuminate\Support\Str;
use Psi\S3EventSns\Casts\EnvironmentCast;
use Psi\S3EventSns\Enums\AwsEventEnvironment;
use Psi\S3EventSns\Enums\EntityType;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

/**
 * Spatie Data Object Documentation
 * https://spatie.be/docs/laravel-data/v2/as-a-data-transfer-object/creating-a-data-object
 */
final class AwsEventS3Data extends Data
{
    public function __construct(
        public readonly string $s3SchemaVersion,
        #[WithCast(EnvironmentCast::class)]
        public readonly AwsEventEnvironment $configurationId,
        #[MapInputName('bucket.ownerIdentity.principalId')]
        public readonly string $bucketOwnerId,
        #[MapInputName('bucket.arn')]
        public readonly string $bucketArn,
        #[MapInputName('bucket.name')]
        public readonly string $bucket,
        #[MapInputName('object.key')]
        public readonly string $key,
        public array $tags = [],
        public array $contents = [],
    ) {}

    public static function withFake(array $attributes): self
    {
        return self::from([
            's3SchemaVersion' => $attributes['s3SchemaVersion'] ?? '1.0',
            'configurationId' => $attributes['configurationId'] ?? 'psi-dev-entity-sync',
            'bucket' => [
                'name' => $attributes['bucket'] ?? 'psi-entity-sync',
                'ownerIdentity' => [
                    'principalId' => $attributes['bucketOwner'] ?? strval(fake()->randomNumber(8)),
                ],
                'arn' => $attributes['arn'] ?? 'arn:aws:s3:::test-bucket-name',
            ],
            'object' => [
                'key' => $attributes['key'] ?? 'local/'.EntityType::random()->value.'/'.Str::uuid().'json',
                'size' => $attributes['size'] ?? 23385,
                'eTag' => $attributes['etag'] ?? 'de258ba81354d22e8fd3570fe914bb1a',
                'sequencer' => $attributes['sequencer'] ?? '00641672AE46DA8F10',
            ],
            'tags' => data_get($attributes, 'tags', []),
        ]);
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'entity' => $this->entity()->value,
        ];
    }

    public function entity(): EntityType
    {
        $value = explode('/', $this->key)[1];
        if (! in_array($value, EntityType::values())) {
            return EntityType::OTHER;
        }

        return EntityType::from($value);
    }

    public function entityId(): string
    {
        return (string) str($this->key)->afterLast('/')->beforeLast('.');
    }
}
