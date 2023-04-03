<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Commit\Contracts\CommitterFactory;
use Junges\Kafka\Config\Sasl;

/** @internal */
interface ConsumerBuilder extends InteractsWithConfigCallbacks
{
    /**
     * Creates a new ConsumerBuilder instance.
     *
     * @param  string  $brokers
     * @param  array  $topics
     * @param  string|null  $groupId
     * @return static
     */
    public static function create(string $brokers, array $topics = [], string $groupId = null): self;

    /**
     * Subscribe to a Kafka topic.
     *
     * @param mixed ...$topics
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function subscribe(...$topics): self;

    /**
     * Set the brokers the kafka consumer should use.
     *
     * @param ?string $brokers
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withBrokers(?string $brokers): self;

    /**
     * Specify the consumer group id.
     *
     * @param ?string $groupId
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withConsumerGroupId(?string $groupId): self;

    /**
     * Specify the commit batch size.
     *
     * @param int $size
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withCommitBatchSize(int $size): self;

    /**
     * Specify the class used to handle consumed messages.
     *
     * @param callable $handler
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withHandler(callable $handler): self;

    /**
     * Specify the class that should be used to deserialize messages.
     *
     * @param MessageDeserializer $deserializer
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function usingDeserializer(MessageDeserializer $deserializer): self;

    /**
     * Specify the factory that should be used to build the committer.
     *
     * @param CommitterFactory $committerFactory
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function usingCommitterFactory(CommitterFactory $committerFactory): self;

    /**
     * Define the max number of messages that should be consumed.
     *
     * @param int $maxMessages
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withMaxMessages(int $maxMessages): self;

    /**
     * Specify the max retries attempts.
     *
     * @param int $maxCommitRetries
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withMaxCommitRetries(int $maxCommitRetries): self;

    /**
     * Set the Dead Letter Queue to be used. If null, the dlq is created from the topic name.
     *
     * @param string|null $dlqTopic
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     * @throws \Junges\Kafka\Exceptions\KafkaConsumerException
     */
    public function withDlq(?string $dlqTopic = null): self;

    /**
     * Set the Sasl configuration.
     *
     * @param Sasl $saslConfig
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withSasl(Sasl $saslConfig): self;

    /**
     * Specify middlewares to be executed before handling the message.
     * The middlewares get executed in the order they are defined.
     * The middleware is a callable in which the first argument is
     * the message itself and the second is the next handler
     *
     * @param callable(mixed, callable): void $middleware
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withMiddleware(callable $middleware): self;

    /**
     * Specify the security protocol that should be used.
     *
     * @param string $securityProtocol
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withSecurityProtocol(string $securityProtocol): self;

    /**
     * Enable or disable consumer auto commit option.
     *
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withAutoCommit(bool $autoCommit = true): self;

    /**
     * Set the configuration options.
     *
     * @param array $options
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withOptions(array $options): self;

    /**
     * Set a specific configuration option.
     *
     * @param string $name
     * @param mixed $value
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withOption(string $name, $value): self;

    /**
     * Enables messages batching
     *
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function enableBatching(): self;

    /**
     * Set batch size limit
     * Batch of messages will be released after batch size exceeds given limit
     *
     * @param int $batchSizeLimit
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withBatchSizeLimit(int $batchSizeLimit): self;

    /**
     * Set batch release interval in milliseconds
     * Batch of messages will be released after timer exceeds given interval
     *
     * @param int $batchReleaseIntervalInMilliseconds
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function withBatchReleaseInterval(int $batchReleaseIntervalInMilliseconds): self;

    /**
     * Enable or disable the read to end option
     *
     * @param bool $stopAfterLastMessage
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function stopAfterLastMessage(bool $stopAfterLastMessage = true): self;

    /**
     * Build the Kafka consumer.
     *
     * @return \Junges\Kafka\Contracts\CanConsumeMessages
     */
    public function build(): CanConsumeMessages;
}
