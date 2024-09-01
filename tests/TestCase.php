<?php

namespace Psi\S3EventSns\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Psi\S3EventSns\Http\Controllers\SnsController;
use Psi\S3EventSns\S3EventSnsServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Psi\\S3EventSns\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            S3EventSnsServiceProvider::class,
            RayServiceProvider::class,
            LaravelDataServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        $this->setupConfig($app, 'data');

        $app['config']->set('app.key', 'base64:8xem+lYvuVAYkd/UvLjmG4cptCp4aOuWCz7Zn7dXcVo=');
        $app['config']->set('app.timezone', 'America/Los_Angeles');

        $app['config']->set('filesystems.disks.s3_event_sns', [
            'driver' => 's3',
            'bucket' => 'test-bucket-name',
        ]);

        $app['config']->set('s3-event-sns.encrypt-key', 'jXn2r5u8x/A?D(G+');
        $app['config']->set('s3-event-sns.storage-region', 'us-west-2');
        $app['config']->set('s3-event-sns.storage-disk', 's3_event_sns');
        $app['config']->set('services.s3-event-sns', [
            'region' => 'us-west-2',
            'key' => 'test-key',
            'secret' => 'test-secret',
        ]);

    }

    protected function defineRoutes($router): void
    {
        $router->addRoute(
            methods: 'POST',
            uri: '/s3-event-sns/aws-sns',
            action: SnsController::class
        )
            ->name('s3-event-sns.webhook');
    }

    protected function setupConfig($app, $config): void
    {
        $array = include __DIR__.'/config/'.$config.'.php';
        foreach ($array as $key => $value) {
            $app['config']->set("{$config}.{$key}", $value);
        }
    }
}
