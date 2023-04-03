<?php

namespace Junges\Kafka;

use Junges\Kafka\Contracts\KafkaMessage;

abstract class AbstractMessage implements KafkaMessage
{
    /**
     * @var string|null
     */
    protected $topicName;
    /**
     * @var int|null
     */
    protected $partition = RD_KAFKA_PARTITION_UA;
    /**
     * @var mixed[]|null
     */
    protected $headers = [];
    /**
     * @var mixed
     */
    protected $body = [];
    /**
     * @var mixed
     */
    protected $key = null;
    /**
     * @param mixed $body
     * @param mixed $key
     */
    public function __construct(?string  $topicName = null, ?int    $partition = RD_KAFKA_PARTITION_UA, ?array  $headers = [], $body = [], $key = null)
    {
        $this->topicName = $topicName;
        $this->partition = $partition;
        $this->headers = $headers;
        $this->body = $body;
        $this->key = $key;
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

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }
}
