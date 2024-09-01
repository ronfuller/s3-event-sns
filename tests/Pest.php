<?php

use Psi\S3EventSns\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function testDir()
{
    return __DIR__;
}

function getRequestData(string $bodyFixtureName, string $headersFixtureName): array
{

    $content = fixture(name: $bodyFixtureName, decode: false);
    $headers = fixture(name: $headersFixtureName);

    // Convert headers to $_SERVER format
    $headers = collect($headers)->mapWithKeys(function ($value, $key) {
        $key = (string) str($key)->upper()->replace('-', '_')->prepend('HTTP_');

        return [$key => $value];
    })->toArray();

    return compact('content', 'headers');
}

function fixture(
    string $name,
    string $extension = '.json',
    bool $decode = true
): array|string {
    $path = '/Fixtures/';
    $name = str($name)->append($extension);

    $contents = file_get_contents(
        filename: testDir().$path.$name,
    );

    if (! $contents) {
        throw new InvalidArgumentException(
            message: "Cannot find fixture: [$name] at $path",
        );
    }

    return match ($extension) {
        '.json' => $decode ? json_decode(json: $contents, associative: true) : $contents,
        default => $contents,
    };

}
