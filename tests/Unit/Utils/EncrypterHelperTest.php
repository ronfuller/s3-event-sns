<?php

use Illuminate\Support\Facades\Crypt;
use Psi\S3EventSns\Utils\EncrypterHelper;

it('should encrypt and decrypt', function () {
    // Act & Assert
    $key = Crypt::generateKey('AES-128-CBC');

    $encrypterHelper = new EncrypterHelper($key);

    $sentence = fake()->sentence();

    $encrypted = $encrypterHelper->encrypt($sentence);
    expect($encrypted)->not()->toBe($sentence)
        ->and($encrypterHelper->decrypt($encrypted))->toBe($sentence);

});

it('should return error when decrypting', function () {
    // Arrange
    $key = Crypt::generateKey('AES-128-CBC');

    $encrypterHelper = new EncrypterHelper($key);

    $sentence = fake()->sentence();

    $encrypted = $encrypterHelper->encrypt($sentence);

    // Act & Assert
    expect(function () use ($encrypterHelper, $encrypted) {
        $encrypterHelper->decrypt($encrypted.'a');
    })->toThrow('DECRYPT ERROR');

});
