<?php

namespace Junges\Kafka\Message;

use Illuminate\Contracts\Support\Arrayable;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Junges\Kafka\AbstractMessage;
use Junges\Kafka\Contracts\KafkaProducerMessage;

class Message extends AbstractMessage implements Arrayable, KafkaProducerMessage
{
    /**
     * Creates a new message instance.
     *
     * @param string|null $topicName
     * @param int $partition
     * @return Message
     */
    public static function create(string $topicName = null, int $partition = RD_KAFKA_PARTITION_UA): KafkaProducerMessage
    {
        return new self($topicName, $partition);
    }

    /**
     * Set a key in the message array.
     *
     * @param string $key
     * @param mixed $message
     * @return $this
     */
    public function withBodyKey(string $key, $message): Message
    {
        $this->body[$key] = $message;

        return $this;
    }

    /**
     * Unset a key in the message array.
     *
     * @param string $key
     * @return $this
     */
    public function forgetBodyKey(string $key): Message
    {
        unset($this->body[$key]);

        return $this;
    }

    /**
     * Set the message headers.
     *
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers = []): \Junges\Kafka\Contracts\KafkaProducerMessage
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Set the kafka message key.
     *
     * @param string|null $key
     * @return $this
     */
    public function withKey(?string $key): \Junges\Kafka\Contracts\KafkaProducerMessage
    {
        $this->key = $key;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'payload' => $this->body,
            'key' => $this->key,
            'headers' => $this->headers,
        ];
    }

    /**
     * @param mixed $body
     */
    public function withBody($body): KafkaProducerMessage
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function withHeader(string $key, $value): KafkaProducerMessage
    {
        $this->headers[$key] = $value;

        return $this;
    }
}
