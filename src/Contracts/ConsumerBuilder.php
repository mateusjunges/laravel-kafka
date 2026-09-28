<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Config\RebalanceStrategy;
use Junges\Kafka\Config\SaslMechanism;
use Junges\Kafka\Config\SecurityProtocol;

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

    /**
     * Defines a callback to be executed when a message is handled as failed, once its retries are used, before
     * it is sent to the dead letter queue, skipped, or stops the consumer. It receives the message and the exception.
     */
    public function onMessageFailed(callable $callback): self;

    /** Set a callback that receives the partitions assigned to this consumer, on every rebalance. */
    public function onPartitionsAssigned(callable $callback): self;

    /**
     * Set a callback that receives the partitions assigned to this consumer, on every rebalance,
     * and returns them with the offsets the consumer should start reading from.
     */
    public function resolveOffsetsUsing(callable $resolver): self;

    /** Set the brokers the kafka consumer should use, instead of the connection brokers. */
    public function withBrokers(string $brokers): self;

    /** Set the consumer group, instead of the group of the connection. */
    public function withGroupId(string $groupId): self;

    /** Specify the handler of the consumed messages, a callable receiving the message and the consumer. */
    public function withHandler(callable|Handler $handler): self;

    /** Specify the class that should be used to deserialize messages. */
    public function usingDeserializer(MessageDeserializer $deserializer): self;

    /** Specify the factory that should be used to build the committer. */
    public function usingCommitterFactory(CommitterFactory $committerFactory): self;

    /** Stop consuming after handling the given number of messages. */
    public function stopAfterMessages(int $messages): self;

    /** Stop consuming after the given number of seconds. */
    public function stopAfterSeconds(int $seconds): self;

    /**
     * Set the Dead Letter Queue to be used. When no topic is given, it is named after the first
     * consumed topic, followed by "-dlq", when the consumer is built.
     */
    public function withDlq(?string $dlqTopic = null): self;

    /**
     * Authenticate this consumer with SASL, using the given credentials instead of the ones of the connection.
     * The security protocol must be SASL_PLAINTEXT or SASL_SSL.
     */
    public function withSasl(
        string $username,
        string $password,
        SaslMechanism|string $mechanism,
        SecurityProtocol|string $securityProtocol = SecurityProtocol::SASL_PLAINTEXT,
    ): self;

    /**
     * Add a middleware the messages go through before being handled. Middlewares run in the order they are added,
     * and receive the message and the next step of the pipeline. Middleware classes given by name are resolved
     * from the service container.
     *
     * @param  Middleware|callable(ConsumerMessage, callable): mixed|class-string<Middleware>  $middleware
     */
    public function withMiddleware(Middleware|callable|string $middleware): self;

    /** Defines a callback that runs before consuming the message. */
    public function beforeConsuming(callable $callable): self;

    /** Defies a callback that runs after consuming the message. */
    public function afterConsuming(callable $callable): self;

    /** Specify the security protocol that should be used. */
    public function withSecurityProtocol(SecurityProtocol|string $securityProtocol): self;

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

    /** Stop consuming once there are no messages left in the assigned partitions. */
    public function stopWhenEmpty(bool $stopWhenEmpty = true): self;

    /**
     * Skip messages that fail when there is no dead letter queue, committing their offsets.
     * By default, the consumer stops without committing the offset of the failed message.
     */
    public function skipFailedMessages(bool $skipFailedMessages = true): self;

    /** Call the handler of a failed message again up to the given number of times before handling it as failed. */
    /**
     * The backoff is the time to wait before each retry, in milliseconds. An array sets the time to wait before
     * each retry in order, like [1000, 5000, 10000], and its last value is used for the remaining retries.
     *
     * @param  int|list<int>  $backoffInMs
     */
    public function retryFailedMessages(int $times, int|array $backoffInMs = 0): self;

    /** Build the Kafka consumer. */
    public function build(): Consumer;
}
