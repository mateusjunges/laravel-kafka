<?php declare(strict_types=1);

namespace Junges\Kafka;

use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Consumers\Builder as ConsumerBuilder;
use Junges\Kafka\Contracts\Manager;
use Junges\Kafka\Producers\PendingMessage;

class Factory implements Manager
{
    use Macroable;

    /** @var array<string, Connection> */
    protected array $connections = [];

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
        return new Connection($config);
    }
}
