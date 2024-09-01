<?php

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psi\S3EventSns\Enums\EntityType;
use Psi\S3EventSns\Services\AwsS3Service;

uses(

);

beforeEach(function () {});

it('should store an entity', function () {
    $uuid = (string) Str::uuid();
    $options = [
        'Tagging' => 'entity='.EntityType::ACCOUNT->value.'&encrypted=false',
        'visibility' => 'private',

    ];
    $contents = [
        'uuid' => $uuid,
        'name' => fake()->name,
        'email' => fake()->email,
    ];

    $args = [
        'path' => "testing/accounts/{$uuid}.json",
        'contents' => \json_encode($contents),
        'options' => $options,
    ];

    Storage::shouldReceive('disk->put')->withArgs(function (
        string $path,
        string $contents,
        mixed $options
    ) use ($args) {
        return $path === $args['path'] && $contents === $args['contents'] && $options === $args['options'];
    })->once()->andReturn(true);

    /**
     * @var AwsS3Service $service
     */
    $service = app(AwsS3Service::class);
    $service->storeEntity(
        entity: EntityType::ACCOUNT,
        contents: $contents,
        uuid: $uuid
    );
});

it('should store an encrypted entity', function () {
    $uuid = (string) Str::uuid();
    $options = [
        'Tagging' => 'entity='.EntityType::ACCOUNT->value.'&encrypted=true',
        'visibility' => 'private',
    ];
    $contents = [
        'uuid' => $uuid,
        'name' => fake()->name,
        'email' => fake()->email,
    ];

    $args = [
        'path' => "testing/accounts/{$uuid}.json",
        'contents' => \json_encode($contents),
        'options' => $options,
    ];

    Storage::shouldReceive('disk->put')->withArgs(function (
        string $path,
        string $contents,
        mixed $options
    ) use ($args) {
        $contents = (new Encrypter(
            key: config('s3-event-sns.encrypt-key'),
            cipher: 'AES-128-CBC'
        ))->decrypt($contents);

        return $path === $args['path'] && $options === $args['options'] && $contents === $args['contents'];
    })->once()->andReturn(true);

    /**
     * @var AwsS3Service $service
     */
    $service = app(AwsS3Service::class);
    $service->storeEntity(
        entity: EntityType::ACCOUNT,
        contents: $contents,
        uuid: $uuid,
        tags: [EntityType::ACCOUNT->value => ['encrypted' => 'true']]
    );
});
