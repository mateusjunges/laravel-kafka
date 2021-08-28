<?php

namespace Junges\Kafka\Tests;

use Junges\Kafka\Contracts\Consumer;
use RdKafka\Message;

class FakeHandler extends Consumer
{
    private ?Message $lastMessage = null;

    public function lastMessage()
    {
        return $this->lastMessage;
    }

    public function handle(Message $message): void
    {
        $this->lastMessage = $message;
    }
}
