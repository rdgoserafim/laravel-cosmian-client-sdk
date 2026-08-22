<?php

namespace Cosmian\LaravelKmsSdk\Tests;

use Cosmian\LaravelKmsSdk\CosmianKmsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [CosmianKmsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cosmian-kms', [
            'url' => 'https://kms.example.test',
            'api_key' => 'secret-token',
            'timeout' => 10,
            'retry_times' => 2,
            'verify_ssl' => true,
        ]);
    }
}