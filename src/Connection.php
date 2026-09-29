<?php declare(strict_types=1);

namespace Junges\Kafka;

use Closure;
use Junges\Kafka\Concerns\InteractsWithConfigCallbacks;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Consumers\Builder as ConsumerBuilder;
use Junges\Kafka\Consumers\PartitionLag;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\Producer as ProducerContract;
use Junges\Kafka\Exceptions\Transactions\TransactionFatalErrorException;
use Junges\Kafka\Exceptions\Transactions\TransactionShouldBeAbortedException;
use Junges\Kafka\Exceptions\Transactions\TransactionShouldBeRetriedException;
use Junges\Kafka\Producers\PendingMessage;
use Junges\Kafka\Producers\Producer;
use LogicException;
use RdKafka\KafkaConsumer;
use RdKafka\Metadata\Partition;
use RdKafka\TopicPartition;
use Throwable;

class Connection
{
    use InteractsWithConfigCallbacks {
        setConfigCallback as storeConfigCallback;
    }

    private ?ProducerContract $producer = null;

    /**
     * The configure consumer closure applies the configuration every consumer of this connection shares, such as the
     * global middlewares, to the builder of each consumer.
     *
     * @param  (Closure(ConsumerBuilder): void)|null  $configureConsumer
     */
    public function __construct(
        private readonly ConnectionConfig $config,
        private readonly ?Closure $configureConsumer = null,
    ) {}

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
        $builder = $this->newConsumerBuilder($topics, $groupId);

        if ($this->configureConsumer instanceof Closure) {
            ($this->configureConsumer)($builder);
        }

        return $builder;
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

    /**
     * Run the callback in a transaction, so the messages it publishes through this connection are
     * delivered all together, or not at all. Retriable commit errors are retried, and when Kafka
     * requires the transaction to be aborted, it is aborted and the callback runs again, up to
     * the given number of attempts. When the callback throws, the transaction is aborted.
     *
     * @template TReturn
     *
     * @param  Closure(self): TReturn  $callback
     * @return TReturn
     *
     * @throws Throwable
     */
    public function transaction(Closure $callback, int $attempts = 3): mixed
    {
        if (! isset([...$this->config->options, ...$this->config->producerOptions]['transactional.id'])) {
            throw new LogicException(
                "Transactions require a [transactional.id] in the producer options of the [{$this->getName()}] Kafka connection."
            );
        }

        $producer = $this->producer();

        for ($attempt = 1; ; $attempt++) {
            $producer->beginTransaction();

            try {
                $result = $callback($this);

                retry(
                    $attempts,
                    fn () => $producer->commitTransaction(),
                    when: fn (Throwable $exception) => $exception instanceof TransactionShouldBeRetriedException,
                );

                return $result;
            } catch (TransactionFatalErrorException $exception) {
                // The producer can't be used anymore, so there is no transaction left to abort.
                throw $exception;
            } catch (Throwable $exception) {
                $this->abortTransaction($producer);

                if (! $exception instanceof TransactionShouldBeAbortedException || $attempt >= $attempts) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * Get how far behind a consumer group is on the given topics, for each of their partitions, comparing the offsets
     * committed by the group with the offsets the next messages published to each partition get. The consumer group
     * is not joined, so its consumers are not rebalanced.
     *
     * @param  list<string>  $topics
     * @return list<PartitionLag>
     *
     * @throws \RdKafka\Exception
     */
    public function lag(string $groupId, array $topics, int $timeoutInMs = 10000): array
    {
        $consumer = $this->makeGroupConsumer($groupId);

        try {
            $partitions = [];

            foreach ($topics as $topic) {
                $metadata = $consumer->getMetadata(false, $consumer->newTopic($topic), $timeoutInMs);

                foreach ($metadata->getTopics() as $topicMetadata) {
                    foreach ($topicMetadata->getPartitions() as $partition) {
                        /** @var Partition $partition */
                        $partitions[] = new TopicPartition($topic, $partition->getId());
                    }
                }
            }

            if ($partitions === []) {
                return [];
            }

            usort($partitions, fn (TopicPartition $a, TopicPartition $b) => [$a->getTopic(), $a->getPartition()] <=> [$b->getTopic(), $b->getPartition()]);

            return array_map(function (TopicPartition $partition) use ($consumer, $timeoutInMs): PartitionLag {
                $low = $high = 0;
                $consumer->queryWatermarkOffsets($partition->getTopic(), $partition->getPartition(), $low, $high, $timeoutInMs);

                // Partitions without a committed offset have a negative one, such as RD_KAFKA_OFFSET_INVALID.
                $committed = $partition->getOffset() >= 0 ? $partition->getOffset() : null;

                return new PartitionLag(
                    topic: $partition->getTopic(),
                    partition: $partition->getPartition(),
                    committedOffset: $committed,
                    lowWatermark: $low,
                    highWatermark: $high,
                    lag: $committed === null ? null : max(0, $high - $committed),
                );
            }, $consumer->getCommittedOffsets($partitions, $timeoutInMs));
        } finally {
            $consumer->close();
        }
    }

    /** Wait until every message queued on this connection is delivered. */
    public function flush(): void
    {
        $this->producer?->flush();
    }

    /** Set a callback for the delivery report of each produced message. */
    public function onDeliveryReport(callable $callback): self
    {
        return $this->setConfigCallback('setDrMsgCb', $callback);
    }

    /**
     * The producer is configured when it is created, and librdkafka can't change its configuration
     * afterwards, so a callback registered later would be silently ignored by the producer.
     */
    protected function setConfigCallback(string $method, callable $callback): self
    {
        if ($this->producer instanceof ProducerContract) {
            throw new LogicException(
                "Configuration callbacks must be registered on the [{$this->getName()}] Kafka connection before its producer is created, "
                .'when the first message is published. Register them in the boot method of a service provider.'
            );
        }

        return $this->storeConfigCallback($method, $callback);
    }

    protected function newConsumerBuilder(array $topics, ?string $groupId): ConsumerBuilder
    {
        return ConsumerBuilder::create($this->getConfig(), $topics, $groupId);
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
                customOptions: $connection->options,
                producerOptions: $connection->producerOptions,
                callbacks: $connection->callbacks,
                flushRetries: $connection->flushRetries,
                flushTimeoutInMs: $connection->flushTimeoutInMs,
                flushRetrySleepInMs: $connection->flushRetrySleepInMs,
                connection: $connection->name,
            ),
            'serializer' => app($connection->serializer ?? MessageSerializer::class),
        ]);
    }

    /**
     * Create a consumer of the given group that only reads offsets. It never subscribes, so it does not join the
     * group, and only keeps the callback providing tokens to authenticate with OAUTHBEARER.
     */
    protected function makeGroupConsumer(string $groupId): KafkaConsumer
    {
        $connection = $this->getConfig();

        $config = new Config(
            broker: $connection->brokers,
            topics: [],
            securityProtocol: $connection->securityProtocol,
            groupId: $groupId,
            sasl: $connection->sasl,
            autoCommit: false,
            customOptions: [...$connection->options, ...$connection->consumerOptions],
            callbacks: array_intersect_key($connection->callbacks, ['setOauthbearerTokenRefreshCb' => true]),
            connection: $connection->name,
        );

        return app(KafkaConsumer::class, ['conf' => $config->makeConf($config->getConsumerOptions())]);
    }

    /** Abort the transaction, reporting a failure to do so instead of hiding the exception that caused it. */
    private function abortTransaction(ProducerContract $producer): void
    {
        try {
            $producer->abortTransaction();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
