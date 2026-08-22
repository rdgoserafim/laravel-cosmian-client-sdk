<?php

namespace Cosmian\LaravelKmsSdk;

use Cosmian\LaravelKmsSdk\Contracts\CosmianKmsInterface;
use Illuminate\Support\ServiceProvider;

class CosmianKmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cosmian-kms.php', 'cosmian-kms');

        $this->app->singleton(CosmianKmsClient::class, function ($app): CosmianKmsClient {
            return new CosmianKmsClient($app['config']->get('cosmian-kms', []));
        });

        $this->app->alias(CosmianKmsClient::class, CosmianKmsInterface::class);
        $this->app->alias(CosmianKmsClient::class, 'cosmian-kms');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/cosmian-kms.php' => config_path('cosmian-kms.php'),
        ], 'cosmian-kms-config');
    }
}