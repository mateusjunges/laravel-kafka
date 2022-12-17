<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\MessageBatch;

/**
 * @internal
 */
interface CanProduceMessages extends InteractsWithConfigCallbacks
{
    /**
     * Return a new Junges\Commit\ProducerBuilder instance
     * @param string|null $broker
     * @return static
     */
    public static function create(string $topic, string $broker = null): self;

    /**
     * Sets a specific config option.
     *
     * @return $this
     */
    public function withConfigOption(string $name, mixed $option): self;

    /**
     * Set offset commit callback to use with consumer groups.
     *
     * @return $this
     */
    public function withOffsetCommitCb(callable $callback): self;

    /**
     * Set rebalance callback for  use with coordinated consumer group balancing.
     *
     * @return $this
     */
    public function withRebalanceCb(callable $callback): self;

    /**
     * Set statistics callback.
     *
     * @return $this
     */
    public function withStatsCb(callable $callback): self;

    /**
     * Sets configuration options.
     *
     * @return $this
     */
    public function withConfigOptions(array $options): self;

    /**
     * Set the message headers.
     *
     * @return $this
     */
    public function withHeaders(array $headers = []): self;

    /**
     * Set the message key.
     *
     * @return $this
     */
    public function withKafkaKey(string $key): self;

    /**
     * Set a message array key.
     *
     * @return $this
     */
    public function withBodyKey(string $key, mixed $message): self;

    /**
     * Set the message to be published.
     *
     * @return $this
     */
    public function withMessage(KafkaProducerMessage $message): self;

    /**
     * Set Sasl configuration.
     *
     * @return $this
     */
    public function withSasl(Sasl $saslConfig): self;

    /**
     * Specifies which serializer should be used.
     */
    public function usingSerializer(MessageSerializer $serializer): self;

    /**
     * Enables or disable debug.
     *
     * @return $this
     */
    public function withDebugEnabled(bool $enabled = true): self;

    /**
     * Returns the topic where the message will be published.
     */
    public function getTopic(): string;

    /**
     * Send the given message to Kakfa.
     *
     * @throws \Exception
     */
    public function send(): bool;

    /**
     * Send a message batch to Kafka.
     *
     * @throws \Junges\Kafka\Exceptions\CouldNotPublishMessage
     */
    public function sendBatch(MessageBatch $messageBatch): int;
}
