<?php

declare(strict_types=1);

namespace Psi\S3EventSns\Concerns;

trait Values
{
    public static function random(): self
    {
        return collect(self::filtered())->random();
    }

    public static function values(): array
    {
        return collect(self::filtered())->map(fn (self $case): string => $case->value)->all();
    }

    public static function has(mixed $value): bool
    {
        return ! is_null($value) && (is_string($value) && \in_array($value, self::values()));
    }

    public function is(string $value): bool
    {
        return $this->value === $value;
    }

    public static function filtered(): array
    {
        return self::cases();
    }
}
