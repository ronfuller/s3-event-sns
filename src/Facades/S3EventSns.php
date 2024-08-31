<?php

namespace Psi\S3EventSns\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Psi\S3EventSns\S3EventSns
 */
class S3EventSns extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Psi\S3EventSns\S3EventSns::class;
    }
}
