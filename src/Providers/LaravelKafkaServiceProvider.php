<?php

namespace Junges\Kafka\Providers;

use Illuminate\Support\ServiceProvider;
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
            $producer = app(Producer::class);

            return new Kafka($producer);
        });
    }
}