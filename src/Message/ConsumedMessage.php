<?php

namespace Junges\Kafka\Message;

use Junges\Kafka\AbstractMessage;
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class ConsumedMessage extends AbstractMessage implements KafkaConsumerMessage
{
    public function __construct(
        protected ?string $topicName,
        protected ?int $partition,
        protected ?array $headers,
        protected mixed $body,
        protected mixed $key,
        protected ?int $offset,
        protected ?int $timestamp,
    ) {
        parent::__construct(
            $this->topicName,
            $this->partition,
            $this->headers,
            $this->body,
            $this->key
        );
    }

    /**
     * Get the offset in which message was published.
     *
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * Get the timestamp the message was published.
     *
     * @return int|null
     */
    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }
}
