<?php

namespace Psi\S3EventSns\Utils;

class FileHelper
{
    public function fileGetContents(string $url): string
    {
        return file_get_contents($url);
    }
}
