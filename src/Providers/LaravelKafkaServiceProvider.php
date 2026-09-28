<?php declare(strict_types=1);

namespace Junges\Kafka\Providers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
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
use Override;
use Throwable;

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

        // Messages are published asynchronously by default, so they are flushed
        // once the application terminates and after each queued job, which may
        // run for a long time inside a single queue worker process.
        $this->app->terminating(fn (Container $app) => $this->flushProducers($app));

        $this->app['events']->listen(
            [JobProcessed::class, JobExceptionOccurred::class],
            fn () => $this->flushProducers($this->app)
        );
    }

    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/kafka.php', 'kafka');

        $this->app->bind(MessageSerializer::class, fn () => new JsonSerializer);

        $this->app->bind(MessageDeserializer::class, fn () => new JsonDeserializer);

        $this->app->bind(ProducerMessage::class, fn () => new Message(''));

        $this->app->bind(ConsumerMessage::class, ConsumedMessage::class);

        $this->app->singleton(Factory::class);

        $this->app->alias(Factory::class, Manager::class);

        $this->app->singleton(LoggerContract::class, Logger::class);
    }

    /**
     * There is nothing left to handle an exception at this point, so delivery
     * failures are reported instead of thrown. They are also dispatched
     * through the CouldNotPublishMessage event.
     */
    private function flushProducers(Container $app): void
    {
        if (! $app->resolved(Factory::class)) {
            return;
        }

        try {
            $app->make(Factory::class)->flush();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function publishesConfiguration(): void
    {
        $this->publishes([
            __DIR__.'/../../config/kafka.php' => config_path('kafka.php'),
        ], 'laravel-kafka-config');
    }
}
