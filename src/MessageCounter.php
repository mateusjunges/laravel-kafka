<?php

namespace Junges\Kafka;

class MessageCounter
{
    private int $messageCount = 0;
    private int $maxMessages;

    public function __construct(int $maxMessages)
    {
        $this->maxMessages = $maxMessages;
    }

    public function add(): self
    {
        $this->messageCount++;

        return $this;
    }

    public function maxMessagesLimitReached(): bool
    {
        return $this->maxMessages === $this->messageCount;
    }
}
