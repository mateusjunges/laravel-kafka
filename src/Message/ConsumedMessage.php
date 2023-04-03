<?php

namespace Junges\Kafka\Message;

use Junges\Kafka\AbstractMessage;
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class ConsumedMessage extends AbstractMessage implements KafkaConsumerMessage
{
    /**
     * @var string|null
     */
    protected $topicName;
    /**
     * @var int|null
     */
    protected $partition;
    /**
     * @var mixed[]|null
     */
    protected $headers;
    /**
     * @var mixed
     */
    protected $body;
    /**
     * @var mixed
     */
    protected $key;
    /**
     * @var int|null
     */
    protected $offset;
    /**
     * @var int|null
     */
    protected $timestamp;
    /**
     * @param mixed $body
     * @param mixed $key
     */
    public function __construct(?string $topicName, ?int $partition, ?array $headers, $body, $key, ?int $offset, ?int $timestamp)
    {
        $this->topicName = $topicName;
        $this->partition = $partition;
        $this->headers = $headers;
        $this->body = $body;
        $this->key = $key;
        $this->offset = $offset;
        $this->timestamp = $timestamp;
        parent::__construct(
            $this->topicName,
            $this->partition,
            $this->headers,
            $this->body,
            $this->key
        );
    }
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }
}
