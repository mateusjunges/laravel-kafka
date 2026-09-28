<?php declare(strict_types=1);

namespace Junges\Kafka;

use Junges\Kafka\Concerns\InteractsWithConfigCallbacks;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Consumers\Builder as ConsumerBuilder;
use Junges\Kafka\Contracts\InteractsWithConfigCallbacks as InteractsWithConfigCallbacksContract;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\Producer as ProducerContract;
use Junges\Kafka\Producers\PendingMessage;
use Junges\Kafka\Producers\Producer;

class Connection implements InteractsWithConfigCallbacksContract
{
    use InteractsWithConfigCallbacks;

    private ?ProducerContract $producer = null;

    public function __construct(private readonly ConnectionConfig $config) {}

    public function getName(): string
    {
        return $this->config->name;
    }

    /** Get the connection configuration, including the registered librdkafka callbacks. */
    public function getConfig(): ConnectionConfig
    {
        return $this->config->withCallbacks([...$this->config->callbacks, ...$this->callbacks]);
    }

    /**
     * Start a message that is queued on this connection's producer when sent. Queued
     * messages are flushed when the application terminates or a queued job finishes.
     */
    public function publish(?string $topic = null): PendingMessage
    {
        return new PendingMessage($this, $topic);
    }

    /** Start a message that is flushed as soon as it is sent. */
    public function publishSync(?string $topic = null): PendingMessage
    {
        return new PendingMessage($this, $topic, sync: true);
    }

    /** Start building a consumer using this connection. */
    public function consumer(array $topics = [], ?string $groupId = null): ConsumerBuilder
    {
        return ConsumerBuilder::create($this->getConfig(), $topics, $groupId);
    }

    /**
     * Get the producer of this connection. It is created on first use and shared by every
     * message published through this connection, so configuration callbacks must be
     * registered before publishing the first message.
     */
    public function producer(): ProducerContract
    {
        return $this->producer ??= $this->makeProducer();
    }

    /** Wait until every message queued on this connection is delivered. */
    public function flush(): void
    {
        $this->producer?->flush();
    }

    protected function makeProducer(): ProducerContract
    {
        $connection = $this->getConfig();

        return app(Producer::class, [
            'config' => new Config(
                broker: $connection->brokers,
                topics: [],
                securityProtocol: $connection->securityProtocol,
                sasl: $connection->sasl,
                customOptions: [...$connection->options, ...$connection->producerOptions],
                callbacks: $connection->callbacks,
                flushRetries: $connection->flushRetries,
                flushTimeoutInMs: $connection->flushTimeoutInMs,
                flushRetrySleepInMs: $connection->flushRetrySleepInMs,
            ),
            'serializer' => app(MessageSerializer::class),
        ]);
    }
}
