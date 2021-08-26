<?php

namespace Junges\Kafka\Providers;

use Illuminate\Support\ServiceProvider;
use Junges\Kafka\Config;
use Junges\Kafka\Kafka;
use RdKafka\Producer;

class LaravelKafkaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {

    }

    public function register(): void
    {
        $this->app->bind(Kafka::class, function() {
            return new Kafka();
        });
    }
}