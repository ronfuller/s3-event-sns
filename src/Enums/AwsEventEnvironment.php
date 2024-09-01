<?php

namespace Psi\S3EventSns\Enums;

use Psi\S3EventSns\Concerns\Values;

/**
 * Documentation on Enums
 * https://stitcher.io/blog/php-enums
 */
enum AwsEventEnvironment: string
{
    use Values;

    case LOCAL = 'dev';
    case STAGING = 'staging';
    case PRODUCTION = 'production';

    case UNKNOWN = 'unknown';

}
