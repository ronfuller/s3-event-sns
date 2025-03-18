<?php

// config for Psi/S3EventSns
return [
    /**
     * Encrypt Key for AWS S3 Encrypted JSON DATA
     */
    'encrypt-key' => env('S3_EVENT_SNS_ENCRYPT_KEY', ''),

    'storage-disk' => env('S3_EVENT_SNS_DISK', 's3-event-sns'),

    'storage-region' => env('S3_EVENT_SNS_REGION', 'us-west-2'),

    /**
     * Buckets to listen for S3 Events
     */
    'buckets' => [
        'psi-entity-sync',
    ],
    'path_filters' => [

    ],

    /**
     * Enable logging of SNS Notification Events
     */
    'logging' => env('S3_EVENT_SNS_ENABLE_LOGGING', false),
];
