<?php

namespace Psi\S3EventSns\Data;

use Psi\S3EventSns\Enums\AwsEventName;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

/**
 * Spatie Data Object Documentation
 * https://spatie.be/docs/laravel-data/v2/as-a-data-transfer-object/creating-a-data-object
 */
final class AwsNotificationEventData extends Data
{
    public function __construct(
        public readonly string $eventVersion,
        public readonly string $eventSource,
        public readonly string $awsRegion,
        public readonly string $eventTime,
        public readonly AwsEventName $eventName,
        #[MapInputName('userIdentity.principalId')]
        public readonly string $principalId,
        #[MapInputName('requestParameters.sourceIPAddress')]
        public readonly string $sourceIPAddress,
        #[MapInputName('responseElements.x-amz-request-id')]
        public readonly string $requestId,
        #[MapInputName('responseElements.x-amz-id-2')]
        public readonly string $id,
        public readonly AwsEventS3Data $s3,

    ) {}

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            's3' => $this->s3->toArray(),
        ];
    }

    public static function withFake(array $attributes): self
    {
        return self::from([
            'eventVersion' => $attributes['eventVersion'] ?? '2.1',
            'eventSource' => $attributes['eventSource'] ?? 'aws:s3',
            'awsRegion' => $attributes['awsRegion'] ?? 'us-west-2',
            'eventTime' => $attributes['eventTime'] ?? '1970-01-01T00:00:00.000Z',
            'eventName' => $attributes['eventName'] ?? AwsEventName::OBJECT_CREATED->value,
            'principalId' => $attributes['principalId'] ?? 'AIDAJDPLRKLG7UEXAMPLE',
            'requestParameters' => [
                'sourceIPAddress' => $attributes['sourceIPAddress'] ?? '255.255.255.0',
            ],
            'responseElements' => [
                'x-amz-request-id' => $attributes['requestId'] ?? 'C3D13FE58DE4C810',
                'x-amz-id-2' => $attributes['id'] ?? 'FMyUVURIY8/IgAtTv8xRjskZQpcIZ9KG4V5Wp6S7S/JRWeUWerMUE5JgHvANOjpD',
            ],
            's3' => AwsEventS3Data::withFake($attributes['s3'] ?? []),

        ]);
    }
}
