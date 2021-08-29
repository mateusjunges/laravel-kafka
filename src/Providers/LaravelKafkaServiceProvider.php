<?php

namespace Junges\Kafka\Providers;

use Illuminate\Support\ServiceProvider;

class LaravelKafkaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishesConfiguration();
    }

    public function register(): void
    {
    }

    private function publishesConfiguration()
    {
        $this->publishes([
            __DIR__."/../../config/kafka.php" => config_path('kafka.php'),
        ]);
    }
}
