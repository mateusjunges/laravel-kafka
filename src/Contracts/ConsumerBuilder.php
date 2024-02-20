<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Config\Sasl;

/** @internal */
interface ConsumerBuilder extends InteractsWithConfigCallbacks
{
    /** Creates a new ConsumerBuilder instance. */
    public static function create(string $brokers, array $topics = [], string $groupId = null): self;

    /** Subscribe to a Kafka topic. */
    public function subscribe(...$topics): self;

    /** Assigns a set of partitions this consumer should consume from. */
    public function assignPartitions(array $partitionAssignment): self;

    /** Defines a callback to be executed when consumer stops consuming messages. */
    public function onStopConsuming(callable $onStopConsuming): self;

    /** Set the brokers the kafka consumer should use. */
    public function withBrokers(?string $brokers): self;

    /** Specify the consumer group id. */
    public function withConsumerGroupId(?string $groupId): self;

    /** Specify the commit batch size. */
    public function withCommitBatchSize(int $size): self;

    /** Specify the class used to handle consumed messages. */
    public function withHandler(callable $handler): self;

    /** Specify the class that should be used to deserialize messages. */
    public function usingDeserializer(MessageDeserializer $deserializer): self;

    /** Specify the factory that should be used to build the committer. */
    public function usingCommitterFactory(CommitterFactory $committerFactory): self;

    /** Define the max number of messages that should be consumed. */
    public function withMaxMessages(int $maxMessages): self;

    /** Specify the max retries attempts. */
    /**
     * Define the max number seconds that a consumer should run
     *
     * @param int $maxTime
     * @return \Junges\Kafka\Consumers\Builder
     */
    public function withMaxTime(int $maxTime): self;

    /**
     * Specify the max retries attempts.
     *
     * @param int $maxCommitRetries
     * @return \Junges\Kafka\Consumers\Builder
     */
    public function withMaxCommitRetries(int $maxCommitRetries): self;

    /**
     * Set the Dead Letter Queue to be used. If null, the dlq is created from the topic name.
     *
     * @throws \Junges\Kafka\Exceptions\ConsumerException
     */
    public function withDlq(?string $dlqTopic = null): self;

    /** Set the Sasl configuration. */
    public function withSasl(string $username, string $password, string $mechanisms, string $securityProtocol = 'SASL_PLAINTEXT'): self;

    /**
     * Specify middlewares to be executed before handling the message.
     * The middlewares get executed in the order they are defined.
     * The middleware is a callable in which the first argument is
     * the message itself and the second is the next handler
     *
     * @param callable(mixed, callable): void $middleware
     * @return \Junges\Kafka\Consumers\Builder
     */
    public function withMiddleware(callable $middleware): self;

    /** Defines a callback that runs before consuming the message. */
    public function beforeConsuming(callable $callable): self;

    /** Defies a callback that runs after consuming the message. */
    public function afterConsuming(callable $callable): self;

    /** Specify the security protocol that should be used. */
    public function withSecurityProtocol(string $securityProtocol): self;

    /** Enable or disable consumer auto commit option. */
    public function withAutoCommit(bool $autoCommit = true): self;

    /** Set the configuration options. */
    public function withOptions(array $options): self;

    /** Set a specific configuration option. */
    public function withOption(string $name, mixed $value): self;

    /** Enables messages batching. */
    public function enableBatching(): self;

    /**
     * Set batch size limit
     * Batch of messages will be released after batch size exceeds given limit.
     */
    public function withBatchSizeLimit(int $batchSizeLimit): self;

    /**
     * Set batch release interval in milliseconds
     * Batch of messages will be released after timer exceeds given interval.
     */
    public function withBatchReleaseInterval(int $batchReleaseIntervalInMilliseconds): self;

    /** Enable or disable the read to end option. */
    public function stopAfterLastMessage(bool $stopAfterLastMessage = true): self;

    /** Build the Kafka consumer. */
    public function build(): MessageConsumer;
}
