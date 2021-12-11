<?php

namespace Junges\Kafka;

class MessageCounter
{
    private int $messageCount = 0;

    public function __construct(private int $maxMessages)
    {
    }

    /**
     * Increases the message count.
     *
     * @return $this
     */
    public function add(): self
    {
        $this->messageCount++;

        return $this;
    }

    /**
     * Gets the number of messages consumed.
     *
     * @return int
     */
    public function messagesCounted(): int
    {
        return $this->messageCount;
    }

    /**
     * Determine if the max messages limit was reached.
     *
     * @return bool
     */
    public function maxMessagesLimitReached(): bool
    {
        return $this->maxMessages === $this->messageCount;
    }
}
