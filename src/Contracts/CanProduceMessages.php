<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Message\Message;

interface CanProduceMessages
{
    public static function create(string $topic, string $broker = null): self;

    /**
     * Set a specific configuration option on the Producer.
     *
     * @param string $name
     * @param string $option
     * @return $this
     */
    public function withConfigOption(string $name, string $option): self;

    /**
     * Set config options based on key/value array.
     *
     * @param array $options
     * @return $this
     */
    public function withConfigOptions(array $options): self;

    /**
     * Set the message headers.
     *
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers): self;

    /**
     * Set the Kafka key that should be used.
     *
     * @param string $key
     * @return $this
     */
    public function withKafkaKey(string $key): self;

    /**
     * Set a given key on the kafka message body.
     *
     * @param string $key
     * @param mixed $message
     * @return $this
     */
    public function withBodyKey(string $key, mixed $message): self;

    /**
     * Set the message to be published based on a Message object.
     *
     * @param Message $message
     * @return $this
     */
    public function withMessage(Message $message): self;

    /**
     * Set the Sasl configuration.
     *
     * @param string $username
     * @param string $password
     * @param string $mechanisms
     * @param string $securityProtocol
     * @return $this
     */
    public function withSasl(string $username, string $password, string $mechanisms, string $securityProtocol = 'SASL_PLAINTEXT'): self;

    /**
     * Specify the class that should be used to serialize messages.
     *
     * @param MessageSerializer $serializer
     * @return $this
     */
    public function usingSerializer(MessageSerializer $serializer): self;

    /**
     * Enable or disable debug on kafka producer.
     *
     * @param bool $enabled
     * @return $this
     */
    public function withDebugEnabled(bool $enabled = true): self;

    /**
     * Disables debug on kafka producer.
     *
     * @return $this
     */
    public function withDebugDisabled(): self;

    /**
     * Get the topic in which the message should be published.
     *
     * @return string
     */
    public function getTopic(): string;

    /**
     * Publish the message to the kafka cluster.
     *
     * @return bool
     */
    public function send(): bool;
}
