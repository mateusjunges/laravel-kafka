<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\MessageBatch;

/** @internal */
interface CanProduceMessages extends InteractsWithConfigCallbacks
{
    /**
     * Return a new Junges\Commit\ProducerBuilder instance
     * @param string $topic
     * @param string|null $broker
     * @return static
     */
    public static function create(string $topic, string $broker = null): self;

    /**
     * Sets a specific config option.
     *
     * @param  string  $name
     * @param  mixed  $option
     * @return $this
     */
    public function withConfigOption(string $name, $option): self;

    /**
     * Set offset commit callback to use with consumer groups.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withOffsetCommitCb(callable $callback): self;

    /**
     * Set rebalance callback for  use with coordinated consumer group balancing.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withRebalanceCb(callable $callback): self;

    /**
     * Set statistics callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withStatsCb(callable $callback): self;

    /**
     * Sets configuration options.
     *
     * @param  array  $options
     * @return $this
     */
    public function withConfigOptions(array $options): self;

    /**
     * Set the message headers.
     *
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers = []): self;

    /**
     * Set the message key.
     *
     * @param string $key
     * @return $this
     */
    public function withKafkaKey(string $key): self;

    /**
     * Set a message array key.
     *
     * @param string $key
     * @param mixed $message
     * @return $this
     */
    public function withBodyKey(string $key, $message): self;

    /**
     * Set the message to be published.
     *
     * @param  \Junges\Kafka\Contracts\KafkaProducerMessage  $message
     * @return $this
     */
    public function withMessage(KafkaProducerMessage $message): self;

    /**
     * Set Sasl configuration.
     *
     * @param  \Junges\Kafka\Config\Sasl  $saslConfig
     * @return $this
     */
    public function withSasl(Sasl $saslConfig): self;

    /**
     * Specifies which serializer should be used.
     *
     * @param  \Junges\Kafka\Contracts\MessageSerializer  $serializer
     * @return \Junges\Kafka\Contracts\CanProduceMessages
     */
    public function usingSerializer(MessageSerializer $serializer): self;

    /**
     * Enables or disable debug.
     *
     * @param  bool  $enabled
     * @return $this
     */
    public function withDebugEnabled(bool $enabled = true): self;

    /**
     * Returns the topic where the message will be published.
     *
     * @return string
     */
    public function getTopic(): string;

    /**
     * Send the given message to Kakfa.
     *
     * @throws \Exception
     * @return bool
     */
    public function send(): bool;

    /**
     * Send a message batch to Kafka.
     *
     * @param  \Junges\Kafka\Producers\MessageBatch  $messageBatch
     * @throws \Junges\Kafka\Exceptions\CouldNotPublishMessage
     * @return int
     */
    public function sendBatch(MessageBatch $messageBatch): int;
}
