<?php

namespace Psi\S3EventSns\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
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

    /**
     * Laravel includes an Illuminate\Queue\Middleware\WithoutOverlapping middleware that allows you to prevent job overlaps based on an arbitrary key.
     * This can be helpful when a queued job is modifying a resource that should only be modified by one job at a time.
     */

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        $key = data_get($this->messageData, 'Records.0.s3.object.key', (string) \Str::uuid());

        return [(new WithoutOverlapping($key))->dontRelease()];
    }
}
