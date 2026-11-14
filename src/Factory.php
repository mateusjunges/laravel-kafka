<?php declare(strict_types=1);

namespace Junges\Kafka;

use Closure;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Consumers\Builder as ConsumerBuilder;
use Junges\Kafka\Contracts\Manager;
use Junges\Kafka\Contracts\Middleware;
use Junges\Kafka\Producers\PendingMessage;

class Factory implements Manager
{
    use Macroable;

    /** @var array<string, Connection> */
    protected array $connections = [];

    /** @var list<Middleware|callable|class-string<Middleware>> */
    protected array $consumerMiddleware = [];

    /** @var list<callable(ConsumerBuilder): mixed> */
    protected array $consumerConfigurationCallbacks = [];

    /** {@inheritDoc} */
    public function connection(?string $name = null): Connection
    {
        $name ??= $this->getDefaultConnection();

        return $this->connections[$name] ??= $this->makeConnection($this->configuration($name));
    }

    /** {@inheritDoc} */
    public function publish(?string $topic = null): PendingMessage
    {
        return $this->connection()->publish($topic);
    }

    /** {@inheritDoc} */
    public function publishSync(?string $topic = null): PendingMessage
    {
        return $this->connection()->publishSync($topic);
    }

    /** {@inheritDoc} */
    public function consumer(array $topics = [], ?string $groupId = null): ConsumerBuilder
    {
        return $this->connection()->consumer($topics, $groupId);
    }

    /** {@inheritDoc} */
    public function consumerFor(KafkaConsumer|string $consumer): ConsumerBuilder
    {
        if (is_string($consumer)) {
            if (! is_subclass_of($consumer, KafkaConsumer::class)) {
                throw new InvalidArgumentException("The consumer [{$consumer}] must extend [".KafkaConsumer::class.'].');
            }

            $consumer = app($consumer);
        }

        return $consumer->toBuilder($this);
    }

    /** {@inheritDoc} */
    public function consumerMiddleware(array|Middleware|Closure|string $middleware): void
    {
        array_push($this->consumerMiddleware, ...(is_array($middleware) ? $middleware : [$middleware]));
    }

    /**
     * Get the middlewares every consumer goes through.
     *
     * @return list<Middleware|callable|class-string<Middleware>>
     */
    public function getConsumerMiddleware(): array
    {
        return $this->consumerMiddleware;
    }

    /** {@inheritDoc} */
    public function configureConsumersUsing(callable $callback): void
    {
        $this->consumerConfigurationCallbacks[] = $callback;
    }

    /**
     * Get the callbacks that configure every consumer.
     *
     * @return list<callable(ConsumerBuilder): mixed>
     */
    public function getConsumerConfigurationCallbacks(): array
    {
        return $this->consumerConfigurationCallbacks;
    }

    /** {@inheritDoc} */
    public function flush(): void
    {
        foreach ($this->connections as $connection) {
            $connection->flush();
        }
    }

    /** {@inheritDoc} */
    public function getDefaultConnection(): string
    {
        return config('kafka.default', 'default');
    }

    protected function configuration(string $name): ConnectionConfig
    {
        $config = config("kafka.connections.{$name}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("The Kafka connection [{$name}] is not configured.");
        }

        return ConnectionConfig::fromArray($name, $config);
    }

    protected function makeConnection(ConnectionConfig $config): Connection
    {
        return new Connection($config, $this->configureConsumer(...));
    }

    /**
     * Apply the global middlewares and configuration callbacks to a consumer. They are applied when each
     * consumer is created, so the ones registered after a connection is resolved are applied as well. The
     * middlewares are added first, so they run before the middlewares of each consumer.
     */
    protected function configureConsumer(ConsumerBuilder $builder): void
    {
        foreach ($this->consumerMiddleware as $middleware) {
            $builder->withMiddleware($middleware);
        }

        foreach ($this->consumerConfigurationCallbacks as $callback) {
            $callback($builder);
        }
    }
}
