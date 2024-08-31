<?php

namespace Psi\S3EventSns;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Psi\S3EventSns\Commands\S3EventSnsCommand;

class S3EventSnsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('s3-event-sns')
            ->hasConfigFile();
    }
}
