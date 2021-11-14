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

    public function setTopicName(string $topic): self
    {
        $this->topicName = $topic;

        return $this;
    }

    public function getTopicName(): ?string
    {
        return $this->topicName;
    }

    public function getPartition(): ?int
    {
        return $this->partition;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function getKey(): mixed
    {
        return $this->key;
    }
}
