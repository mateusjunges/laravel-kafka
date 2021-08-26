<?php

namespace Junges\Kafka\Tests;

use Junges\Kafka\Providers\LaravelKafkaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelKafkaServiceProvider::class,
        ];
    }
}
