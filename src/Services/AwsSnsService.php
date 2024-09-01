<?php

declare(strict_types=1);

namespace Psi\S3EventSns\Services;

use Aws\Credentials\Credentials;
use Aws\Sns\MessageValidator;
use Aws\Sns\SnsClient;
use Psi\S3EventSns\Services\Aws\Message;
use Psi\S3EventSns\Utils\FileHelper;
use Throwable;

class AwsSnsService
{
    public function __construct(
        protected string $region,
        protected string $awsKey,
        protected string $awsSecret
    ) {}

    protected function client(): SnsClient
    {
        return new SnsClient([
            'version' => '2010-03-31',
            'region' => $this->region,
            'credentials' => new Credentials(
                $this->awsKey,
                $this->awsSecret
            ),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            $message = Message::fromRawPostData();

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

                    if (config('s3-event-sns.logging')) {
                        logger()->info('SNS Notification', context: [
                            'subject' => $subject,
                        ]);
                    }

                    if ($subject === 'Amazon S3 Notification') {
                        /** @var AwsS3NotificationService $service */
                        $service = app(AwsS3NotificationService::class);

                        $service->handle(payload: $messageData);
                    }

                }
            }
        } catch (Throwable $th) {
            logger()->error('SNS Notification Error', context: [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            throw $th;
        }
    }
}
