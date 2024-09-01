<?php

namespace Psi\S3EventSns\Enums;

use Psi\S3EventSns\Concerns\Values;

/**
 * Documentation on Enums
 * https://stitcher.io/blog/php-enums
 */
enum EntityType: string
{
    use Values;

    case ACCOUNT = 'accounts';
    case ORDER = 'orders';
    case INVOICE = 'invoices';
    case APPLICATION = 'applications';
    case REFERENCE = 'references';
    case FILE = 'files';
    case OTHER = 'other';

    public function tags(array $attributes = []): array
    {
        return match ($this) {
            self::ACCOUNT => [
                'entity' => self::ACCOUNT->value,
                'encrypted' => data_get($attributes, self::ACCOUNT->value.'.encrypted') ?? 'false',
            ],
            self::ORDER => [
                'entity' => self::ORDER->value,
                'encrypted' => data_get($attributes, self::ORDER->value.'.encrypted') ?? 'true',
            ],
            self::INVOICE => [
                'entity' => self::INVOICE->value,
                'encrypted' => data_get($attributes, self::INVOICE->value.'.encrypted') ?? 'false',
            ],
            self::APPLICATION => [
                'entity' => self::APPLICATION->value,
                'encrypted' => data_get($attributes, self::APPLICATION->value.'.encrypted') ?? 'true',
            ],
            self::REFERENCE => [
                'entity' => self::REFERENCE->value,
                'encrypted' => data_get($attributes, self::REFERENCE->value.'.encrypted') ?? 'false',
            ],
            self::FILE => [
                'entity' => self::FILE->value,
                'encrypted' => data_get($attributes, self::FILE->value.'.encrypted') ?? 'false',
            ],
            self::OTHER => [
                'entity' => self::OTHER->value,
                'encrypted' => data_get($attributes, self::OTHER->value.'.encrypted') ?? 'false',
            ],
        };
    }
}
