<?php

namespace Junges\Kafka\Providers;

use Illuminate\Support\ServiceProvider;
use Junges\Kafka\Console\Commands\KafkaConsumerCommand;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Message\Serializers\JsonSerializer;

class LaravelKafkaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishesConfiguration();

        if ($this->app->runningInConsole()) {
            $this->commands([
                KafkaConsumerCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->bind(MessageSerializer::class, function () {
            return new JsonSerializer();
        });

        $this->app->bind(MessageDeserializer::class, function () {
            return new JsonDeserializer();
        });

        $this->app->bind(KafkaProducerMessage::class, function () {
            return new Message('');
        });

        $this->app->bind(KafkaConsumerMessage::class, ConsumedMessage::class);
    }

    private function publishesConfiguration()
    {
        $this->publishes([
            __DIR__."/../../config/kafka.php" => config_path('kafka.php'),
        ], 'laravel-kafka-config');
    }
}
