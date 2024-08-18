<?php declare(strict_types=1);

namespace Junges\Kafka\Providers;

use Illuminate\Support\ServiceProvider;
use Junges\Kafka\Console\Commands\ConsumerCommand;
use Junges\Kafka\Console\Commands\RestartConsumersCommand;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Logger as LoggerContract;
use Junges\Kafka\Contracts\Manager;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Factory;
use Junges\Kafka\Logger;
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
                ConsumerCommand::class,
                RestartConsumersCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->bind(MessageSerializer::class, fn () => new JsonSerializer());

        $this->app->bind(MessageDeserializer::class, fn () => new JsonDeserializer());

        $this->app->bind(ProducerMessage::class, fn () => new Message(''));

        $this->app->bind(ConsumerMessage::class, ConsumedMessage::class);

        $this->app->bind(Manager::class, Factory::class);

        $this->app->singleton(LoggerContract::class, Logger::class);
    }

    private function publishesConfiguration(): void
    {
        $this->publishes([
            __DIR__."/../../config/kafka.php" => config_path('kafka.php'),
        ], 'laravel-kafka-config');
    }
}
