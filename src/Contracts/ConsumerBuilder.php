<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Config\RebalanceStrategy;

interface ConsumerBuilder extends InteractsWithConfigCallbacks
{
    /** Creates a new ConsumerBuilder instance for the given connection. */
    public static function create(ConnectionConfig $connection, array $topics = [], ?string $groupId = null): self;

    /** Subscribe to a Kafka topic. */
    public function subscribe(...$topics): self;

    /** Assigns a set of partitions this consumer should consume from. */
    public function assignPartitions(array $partitionAssignment): self;

    /** Defines a callback to be executed when consumer stops consuming messages. */
    public function onStopConsuming(callable $onStopConsuming): self;

    /** Set a callback to be executed when partitions are assigned to this consumer. */
    public function withPartitionAssignmentCallback(callable $callback): self;

    /** Set dynamic partition assignment with offset provider callback. */
    public function assignPartitionsWithOffsets(callable $offsetProvider): self;

    /** Set the brokers the kafka consumer should use, instead of the connection brokers. */
    public function withBrokers(string $brokers): self;

    /** Specify the consumer group id. */
    public function withConsumerGroupId(?string $groupId): self;

    /** Specify the class used to handle consumed messages. */
    public function withHandler(callable $handler): self;

    /** Specify the class that should be used to deserialize messages. */
    public function usingDeserializer(MessageDeserializer $deserializer): self;

    /** Specify the factory that should be used to build the committer. */
    public function usingCommitterFactory(CommitterFactory $committerFactory): self;

    /** Define the max number of messages that should be consumed. */
    public function withMaxMessages(int $maxMessages): self;

    /** Define the max number seconds that a consumer should run. */
    public function withMaxTime(int $maxTime): self;

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
     * @param  callable(mixed, callable): void  $middleware
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

    /** Enables manual commit. */
    public function withManualCommit(): self;

    /** Set the partition assignment (rebalance) strategy for consumer groups. */
    public function withRebalanceStrategy(RebalanceStrategy|string $strategy): self;

    /** Set the configuration options. */
    public function withOptions(array $options): self;

    /** Set a specific configuration option. */
    public function withOption(string $name, mixed $value): self;

    /** Enable or disable the read to end option. */
    public function stopAfterLastMessage(bool $stopAfterLastMessage = true): self;

    /**
     * Skip messages that fail when there is no dead letter queue, committing their offsets.
     * By default, the consumer stops without committing the offset of the failed message.
     */
    public function skipFailedMessages(bool $skipFailedMessages = true): self;

    /** Call the handler of a failed message again up to the given number of times before handling it as failed. */
    public function retryFailedMessages(int $times, int $backoffInMs = 0): self;

    /** Build the Kafka consumer. */
    public function build(): MessageConsumer;
}
