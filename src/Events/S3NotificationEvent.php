<?php

namespace Psi\S3EventSns\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Psi\S3EventSns\Data\AwsNotificationEventData;

class S3NotificationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public AwsNotificationEventData $eventData) {}
}
