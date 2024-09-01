<?php

declare(strict_types=1);

namespace Psi\S3EventSns\Casts;

use Psi\S3EventSns\Enums\AwsEventEnvironment;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\DataProperty;

class EnvironmentCast implements Cast
{
    public function __construct() {}

    public function cast(DataProperty $property, mixed $value, array $context): AwsEventEnvironment
    {
        $enumValue = (string) str(strval($value))->after('-')->before('-');

        return AwsEventEnvironment::has($enumValue) ? AwsEventEnvironment::from($enumValue) : AwsEventEnvironment::UNKNOWN;
    }
}
