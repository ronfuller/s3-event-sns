<?php

namespace Psi\S3EventSns;

use Illuminate\Support\Facades\Route;
use Psi\S3EventSns\Http\Controllers\SnsController;
use Psi\S3EventSns\Services\AwsS3NotificationService;
use Psi\S3EventSns\Services\AwsS3Service;
use Psi\S3EventSns\Services\AwsSnsService;
use Psi\S3EventSns\Utils\EncrypterHelper;
use Psi\S3EventSns\Utils\FileHelper;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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

    public function packageRegistered(): void
    {

        $this->app->singleton(
            abstract: AwsSnsService::class,
            concrete: fn () => new AwsSnsService(
                region: strval(config('services.s3-event-sns.region') ?? 'us-west-2'),
                awsKey: strval(config('services.s3-event-sns.key') ?? ''),
                awsSecret: strval(config('services.s3-event-sns.secret') ?? ''),
            )
        );

        $this->app->singleton(
            abstract: AwsS3Service::class,
            concrete: fn () => new AwsS3Service(
                region: strval(config('s3-event-sns.storage-region')),
                encryptKey: strval(config('s3-event-sns.encrypt-key')),
                disk: strval(config('s3-event-sns.storage-disk')),
            )
        );

        $this->app->singleton(
            abstract: AwsS3NotificationService::class,
            concrete: fn () => new AwsS3NotificationService(
                buckets: config('s3-event-sns.buckets') ?? [],
            )
        );

        $this->app->singleton(
            abstract: EncrypterHelper::class,
            concrete: fn () => new EncrypterHelper(
                key: strval(config('s3-event-sns.encrypt-key'))
            )
        );

        $this->app->singleton(
            abstract: FileHelper::class,
            concrete: fn () => new FileHelper
        );

        Route::macro('s3EventSns', function (string $baseUrl = 's3-event-sns') {
            Route::prefix($baseUrl)->group(function () {
                Route::post('/aws-sns', SnsController::class)->name('s3-event-sns.webhook');
            });
        });

    }
}
