<?php

use Psi\S3EventSns\Services\AwsS3NotificationService;
use Psi\S3EventSns\Utils\FileHelper;

use function Pest\Laravel\call;

it('should handle an SNS subscription', function () {
    // Arrange
    $data = getRequestData('SubscriptionConfirmationEvent', 'SubscriptionConfirmationHeaders'); // @phpstan-ignore-line

    $this->mock(FileHelper::class, function ($mock) {                   // @phpstan-ignore-line
        $mock->shouldReceive('fileGetContents')->once();
    });

    // Act & Assert
    call(
        method: 'POST',
        uri: route('s3-event-sns.webhook'),
        server: $data['headers'],
        content: $data['content']
    )->assertSuccessful();

})->skip('Come back to this');

it('should handle an sns subscription error', function () {
    config(['s3-event-sns.logging' => true]);
    // Arrange
    $data = getRequestData('SubscriptionConfirmationEvent', 'SubscriptionConfirmationHeaders'); // @phpstan-ignore-line

    $this->mock(FileHelper::class, function ($mock) {                   // @phpstan-ignore-line
        $mock->shouldReceive('fileGetContents')->andThrow(new Exception('Error in confirming subscription'));
    });

    \Illuminate\Support\Facades\Log::shouldReceive('error')->once();

    // Act & Assert
    call(
        method: 'POST',
        uri: route('s3-event-sns.webhook'),
        server: $data['headers'],
        content: $data['content']
    )->assertServerError();

});

it('should handle an SNS notification', function () {
    // Arrange
    $data = getRequestData('NotificationEvent', 'NotificationHeaders');

    $messageData = json_decode(json_decode($data['content'], associative: true)['Message'], associative: true);

    $this->mock(AwsS3NotificationService::class, function ($mock) use ($messageData) {                   // @phpstan-ignore-line
        $mock->shouldReceive('handle')->once()->with($messageData);
    });

    // Act & Assert
    call(
        method: 'POST',
        uri: route('s3-event-sns.webhook'),
        server: $data['headers'],
        content: $data['content']
    )->assertSuccessful();

})->skip('come back to this');
