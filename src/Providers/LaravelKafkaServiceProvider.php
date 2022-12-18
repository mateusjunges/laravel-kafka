<?php declare(strict_types=1);

namespace Junges\Kafka\Providers;

use Illuminate\Support\ServiceProvider;
use Junges\Kafka\Console\Commands\KafkaConsumerCommand;
use Junges\Kafka\Console\Commands\KafkaRestartConsumersCommand;
use Junges\Kafka\Contracts\CanConsumeMessagesFromKafka;
use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Kafka;
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
                KafkaRestartConsumersCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->bind(MessageSerializer::class, fn() => new JsonSerializer());

        $this->app->bind(MessageDeserializer::class, fn() => new JsonDeserializer());

        $this->app->bind(KafkaProducerMessage::class, fn() => new Message(''));

        $this->app->bind(KafkaConsumerMessage::class, ConsumedMessage::class);

        $this->app->bind(CanPublishMessagesToKafka::class, Kafka::class);

        $this->app->bind(CanConsumeMessagesFromKafka::class, Kafka::class);
    }

    private function publishesConfiguration()
    {
        $this->publishes([
            __DIR__."/../../config/kafka.php" => config_path('kafka.php'),
        ], 'laravel-kafka-config');
    }
}
