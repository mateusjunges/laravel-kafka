<?php

namespace Junges\Kafka;

use Junges\Kafka\Contracts\KafkaMessage;

abstract class AbstractMessage implements KafkaMessage
{
    public function __construct(
        protected ?string  $topicName = null,
        protected ?int    $partition = RD_KAFKA_PARTITION_UA,
        protected ?array  $headers = [],
        protected mixed   $body = [],
        protected mixed   $key = null,
    ) {
    }

    public function setPartition(int $partition): self
    {
        $this->partition = $partition;

        return $this;
    }

    /**
     * Set the message topic.
     *
     * @param string $topic
     * @return $this
     */
    public function setTopicName(string $topic): self
    {
        $this->topicName = $topic;

        return $this;
    }

    /**
     * Get the topic where the message was published.
     *
     * @return string|null
     */
    public function getTopicName(): ?string
    {
        return $this->topicName;
    }

    /**
     * Get the partition in which the message was published.
     *
     * @return int|null
     */
    public function getPartition(): ?int
    {
        return $this->partition;
    }

    /**
     * Get the published message body.
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get the published message headers.
     *
     * @return array|null
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    /**
     * Get the published message key.
     *
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->key;
    }
}
