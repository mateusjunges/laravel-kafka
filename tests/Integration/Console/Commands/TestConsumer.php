<?php

namespace Junges\Kafka\Tests\Integration\Console\Commands;

use RdKafka\Message;

class TestConsumer
{
    public static Message $message;

    public function handle(Message $message): void
    {
        self::$message = $message;
    }
}
