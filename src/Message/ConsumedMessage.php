<?php declare(strict_types=1);

namespace Junges\Kafka\Message;

use Junges\Kafka\AbstractMessage;
use Junges\Kafka\Contracts\ConsumerMessage;

class ConsumedMessage extends AbstractMessage implements ConsumerMessage
{
    public function __construct(
        protected ?string $topicName = null,
        protected ?int $partition = 0,
        protected ?array $headers = [],
        protected mixed $body = null,
        protected mixed $key = null,
        protected ?int $offset = 0,
        protected ?int $timestamp = null,
        protected int $attempts = 1,
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

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function withAttempts(int $attempts): static
    {
        $message = clone $this;
        $message->attempts = $attempts;

        return $message;
    }
}
