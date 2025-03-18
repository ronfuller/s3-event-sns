<?php

namespace Psi\S3EventSns\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psi\S3EventSns\Services\AwsS3NotificationService;

class AwsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $messageData) {}

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        /** @var AwsS3NotificationService $service */
        $service = app(AwsS3NotificationService::class);

        $service->handle(payload: $this->messageData);
    }
}
