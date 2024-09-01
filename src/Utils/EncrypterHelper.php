<?php

declare(strict_types=1);

namespace Psi\S3EventSns\Utils;

use Illuminate\Encryption\Encrypter;
use Throwable;

class EncrypterHelper
{
    protected Encrypter $encrypter;

    public function __construct(
        public string $key,
    ) {
        $this->encrypter = new Encrypter($key);
    }

    public function encrypt(string $value): string
    {
        return $this->encrypter->encrypt(value: $value);
    }

    /**
     * @throws Throwable
     */
    public function decrypt(string $payload, string $error = 'DECRYPT ERROR'): string
    {
        try {
            return $this->encrypter->decrypt(payload: $payload);
        } catch (\Throwable $th) {
            throw new \Exception($error);
        }
    }
}
