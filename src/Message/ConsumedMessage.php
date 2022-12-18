<?php declare(strict_types=1);

namespace Junges\Kafka\Message;

use Junges\Kafka\AbstractMessage;
use Junges\Kafka\Contracts\ConsumerMessage;

class ConsumedMessage extends AbstractMessage implements ConsumerMessage
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

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }
}
