<?php

/**
 * Pest Expectations: https://pestphp.com/docs/expectations#available-expectations
 */

use Psi\S3EventSns\Data\AwsNotificationEventData;
use Psi\S3EventSns\Enums\EntityType;

beforeEach(function () {});

it('should create a new AwsNotificationEventData object', function () {
    $data = AwsNotificationEventData::withFake([]);
    expect($data)->toBeInstanceOf(AwsNotificationEventData::class);
});

it('should output an array', function () {
    $data = AwsNotificationEventData::withFake([]);
    expect($data->toArray())->toHaveKeys(['s3.bucket', 's3.key', 's3.entity']);
});

it('should output an s3 array', function () {
    $data = AwsNotificationEventData::withFake([]);
    expect($data->s3->toArray())->toHaveKeys(['bucket', 'key', 'entity'])
        ->and($data->s3->entity())->toBeInstanceOf(EntityType::class);
});

it('should have an entity type', function (string $key, EntityType $entityType) {
    $data = AwsNotificationEventData::withFake([
        's3' => [
            'key' => $key,
        ],
    ]);
    expect($data->s3->entity())->toBeInstanceOf(EntityType::class)
        ->and($data->s3->entity())->toBe($entityType);
})
    ->with([

        'accounts' => [
            'local/accounts/8d82ddeb-1e39-4ad8-bfd5-fcb78c10c077.json', EntityType::ACCOUNT,
        ],
        'orders' => [
            'local/orders/8d82ddeb-1e39-4ad8-bfd5-fcb78c10c077.json', EntityType::ORDER,
        ],
        'applications' => [
            'local/applications/8d82ddeb-1e39-4ad8-bfd5-fcb78c10c077.json', EntityType::APPLICATION,
        ],
        'references' => [
            'local/references/8d82ddeb-1e39-4ad8-bfd5-fcb78c10c077.json', EntityType::REFERENCE,
        ],
        'files' => [
            'local/files/8d82ddeb-1e39-4ad8-bfd5-fcb78c10c077.json', EntityType::FILE,
        ],
        'invoices' => [
            'local/invoices/8d82ddeb-1e39-4ad8-bfd5-fcb78c10c077.json', EntityType::INVOICE,
        ],
        'payments' => [
            'local/payments/8d82ddeb-1e39-4ad8-bfd5-fcb78c10c077.json', EntityType::OTHER,
        ],
    ]);
