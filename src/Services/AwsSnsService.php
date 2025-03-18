<?php

declare(strict_types=1);

namespace Psi\S3EventSns\Services;

use Aws\Sns\MessageValidator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Traits\Conditionable;
use Psi\S3EventSns\Jobs\AwsNotificationJob;
use Psi\S3EventSns\Services\Aws\Message;
use Psi\S3EventSns\Utils\FileHelper;
use Throwable;

class AwsSnsService
{
    use Conditionable;

    public bool $logging;

    public function __construct(
        protected string $region,
        protected string $awsKey,
        protected string $awsSecret
    ) {
        $this->logging = Config::boolean('s3-event-sns.logging');

    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            $message = Message::fromRawPostData();

            $this->when(
                value: $this->logging,
                callback: fn () => logger()->info('SNS Raw Message', context: [
                    'message' => $message->toArray(),
                ])
            );
            // make validator instance
            $validator = new MessageValidator;

            // Validate the message
            if ($validator->isValid($message)) {
                if ($message['Type'] == 'SubscriptionConfirmation') {
                    // if it's subscription or unsubscribe event then call SubscribeURL

                    /** @var FileHelper $fileHelper */
                    $fileHelper = app(FileHelper::class);       // use FileHelper class for testability, instead of using file_get_contents directly

                    $fileHelper->fileGetContents($message['SubscribeURL']);

                } elseif ($message['Type'] === 'Notification') {
                    $subject = $message['Subject'];
                    $messageData = json_decode($message['Message'], associative: true);

                    $this->when(
                        value: $this->logging,
                        callback: fn () => logger()->info('SNS Notification', context: [
                            'message' => $messageData,
                        ])
                    );

                    $filters = Config::array(
                        's3-event-sns.path_filters',
                        []
                    );

                    if (! empty($filters)) {
                        $objectKey = $messageData['Records'][0]['s3']['object']['key'];
                        /** @phpstan-ignore-next-line  */
                        $hasKeyPath = collect($filters)->contains(function ($filter) use ($objectKey) {
                            return str_contains($objectKey, $filter);
                        });

                        if (! $hasKeyPath) {

                            $this->when(
                                value: $this->logging,
                                callback: fn () => logger()->info('SNS Notification Ignored', context: [
                                    'message' => $messageData,
                                    'filters' => $filters,
                                    'objectKey' => $objectKey,
                                ])
                            );

                            return;
                        }

                    }

                    if ($subject === 'Amazon S3 Notification') {
                        \dispatch(new AwsNotificationJob($messageData));
                    }

                }
            }
        } catch (Throwable $th) {
            $this->when(
                value: $this->logging,
                callback: fn () => logger()->error('SNS Notification Error', context: [
                    'message' => $th->getMessage(),
                    'trace' => $th->getTraceAsString(),
                ])
            );

            throw $th;
        }
    }
}
