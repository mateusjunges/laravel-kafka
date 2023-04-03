<?php

namespace Junges\Kafka;

class MessageCounter
{
    /**
     * @var int
     */
    private $messageCount = 0;
    /**
     * @var int
     */
    private $maxMessages;

    public function __construct(int $maxMessages)
    {
        $this->maxMessages = $maxMessages;
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
