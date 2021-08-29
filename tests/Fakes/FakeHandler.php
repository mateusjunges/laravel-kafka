<?php

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Contracts\Consumer;
use RdKafka\Message;

class FakeHandler extends Consumer
{
    private ?Message $lastMessage = null;

    public function lastMessage(): ?Message
    {
        return $this->lastMessage;
    }

    public function handle(Message $message): void
    {
        $this->lastMessage = $message;
    }
}
