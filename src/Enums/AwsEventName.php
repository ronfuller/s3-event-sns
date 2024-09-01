<?php

namespace Psi\S3EventSns\Enums;

use Psi\S3EventSns\Concerns\Values;

/**
 * Documentation on Enums
 * https://stitcher.io/blog/php-enums
 */
enum AwsEventName: string
{
    use Values;

    case OBJECT_DELETED = 'ObjectRemoved:DeleteMarkerCreated';
    case OBJECT_CREATED = 'ObjectCreated:Put';
}
