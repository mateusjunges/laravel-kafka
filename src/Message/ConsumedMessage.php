<?php declare(strict_types=1);

namespace Junges\Kafka\Message;

use Junges\Kafka\AbstractMessage;
use Junges\Kafka\Contracts\ConsumerMessage;

class ConsumedMessage extends AbstractMessage implements ConsumerMessage
{
    public function __construct(
        ?string $topicName = null,
        ?int $partition = 0,
        ?array $headers = [],
        mixed $body = null,
        mixed $key = null,
        protected ?int $offset = 0,
        protected ?int $timestamp = null,
        protected int $attempts = 1,
    ) {
        parent::__construct($topicName, $partition, $headers, $body, $key);
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
