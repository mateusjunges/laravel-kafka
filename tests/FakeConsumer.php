<?php

namespace Junges\Kafka\Tests;

use RdKafka\Message;

class FakeConsumer
{
    private Message $message;

    public function __invoke(Message $message)
    {
        $this->message = $message;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
