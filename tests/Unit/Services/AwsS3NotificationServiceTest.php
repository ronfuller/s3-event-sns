<?php

use Illuminate\Support\Facades\Event;
use Psi\S3EventSns\Events\S3NotificationEvent;
use Psi\S3EventSns\Services\AwsS3NotificationService;
use Psi\S3EventSns\Services\AwsS3Service;

beforeEach(function () {});

it('should handle a SNS AWS S3 Event', function () {
    // Arrange
    Event::fake([
        S3NotificationEvent::class,
    ]);

    config(['s3-event-sns.buckets' => ['psi-entity-sync']]);

    $this->mock(AwsS3Service::class, function ($mock) {         // @phpstan-ignore-line
        $mock->shouldReceive('getTags')->once()->with(
            'psi-entity-sync',
            'local/orders/2143892.json'
        )->andReturn([]);

        $mock->shouldReceive('getContents')->once()->andReturn([
            'id' => 1,
        ]);
    });

    $data = getRequestData('NotificationEvent', 'NotificationHeaders'); // @phpstan-ignore-line

    $payload = json_decode(json_decode($data['content'], associative: true)['Message'], associative: true);

    /** @var AwsS3NotificationService $service */
    $service = app(AwsS3NotificationService::class);

    $service->handle($payload);

    Event::assertDispatched(S3NotificationEvent::class);
});

it('should log an error on a failed AWS service call', function () {
    // Arrange
    Event::fake([
        S3NotificationEvent::class,
    ]);

    config(['s3-event-sns.buckets' => ['psi-entity-sync']]);

    $this->mock(AwsS3Service::class, function ($mock) {         // @phpstan-ignore-line
        $mock->shouldReceive('getTags')->once()->with(
            'psi-entity-sync',
            'local/orders/2143892.json'
        )->andReturn([]);

        // Return an empty array to simulate an error
        $mock->shouldReceive('getContents')->once()->andReturn([

        ]);
    });

    Log::shouldReceive('error')->once();

    $data = getRequestData('NotificationEvent', 'NotificationHeaders'); // @phpstan-ignore-line

    $payload = json_decode(json_decode($data['content'], associative: true)['Message'], associative: true);

    /** @var AwsS3NotificationService $service */
    $service = app(AwsS3NotificationService::class);

    $service->handle($payload);

    Event::assertNotDispatched(S3NotificationEvent::class);
});
