<?php

namespace Junges\Kafka;

class MessageCounter
{
    private int $messageCount = 0;

    public function __construct(private int $maxMessages)
    {
    }

    public function add(): self
    {
        $this->messageCount++;

        return $this;
    }

    public function messagesCounted(): int
    {
        return $this->messageCount;
    }

    public function maxMessagesLimitReached(): bool
    {
        return $this->maxMessages === $this->messageCount;
    }
}
