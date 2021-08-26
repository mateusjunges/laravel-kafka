<?php

namespace Junges\Kafka\Providers;

use Illuminate\Support\ServiceProvider;
use Junges\Kafka\Kafka;

class LaravelKafkaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
    }

    public function register(): void
    {
        $this->app->bind(Kafka::class, function () {
            return new Kafka();
        });
    }
}
