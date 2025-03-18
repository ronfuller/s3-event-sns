<?php

declare(strict_types=1);

namespace Psi\S3EventSns\Services;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Traits\Conditionable;
use Psi\S3EventSns\Data\AwsNotificationEventData;
use Psi\S3EventSns\Events\S3NotificationEvent;

class AwsS3NotificationService
{
    use Conditionable;

    protected bool $logging;

    public function __construct(protected array $buckets)
    {
        $this->logging = Config::boolean('s3-event-sns.logging');
    }

    /**
     * @throws Exception
     */
    public function handle(array $payload): void
    {
        $eventData = AwsNotificationEventData::from(data_get($payload, 'Records.0'));

        $this->when(
            value: $this->logging,
            callback: fn () => logger()->info('Aws Notification Service', context: [
                'event' => $eventData->toArray(),
            ])
        );

        /**
         * @var AwsS3Service $service
         */
        $service = app(AwsS3Service::class);

        $eventData->s3->tags = $service->getTags(
            bucket: $eventData->s3->bucket,
            key: $eventData->s3->key
        );

        if (\in_array($eventData->s3->bucket, $this->buckets)) {
            $encrypted = data_get($eventData->s3->tags, 'encrypted', 'false') === 'true';

            $eventData->s3->contents = $service->getContents(
                bucket: $eventData->s3->bucket,
                key: $eventData->s3->key,
                encrypted: $encrypted
            );

            if (empty($eventData->s3->contents)) {
                logger()->error("Error in getting content for key.  Bucket: {$eventData->s3->bucket}, Key: {$eventData->s3->key}. Check encryption tags on s3 object and encryption key.");

                return;
            }
            event(new S3NotificationEvent($eventData));
        }

    }
}
