<?php

namespace Junges\Kafka\Providers;

use Illuminate\Support\ServiceProvider;
use Junges\Kafka\Facades\Kafka as KafkaFacade;
use Junges\Kafka\Kafka;

class LaravelKafkaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishesConfiguration();
    }

    public function register(): void
    {
        $this->app->bind(KafkaFacade::class, function () {
            return new Kafka();
        });
    }

    private function publishesConfiguration()
    {
        $this->publishes([
            __DIR__."/../../config/kafka.php" => config_path('kafka.php'),
        ]);
    }
}
