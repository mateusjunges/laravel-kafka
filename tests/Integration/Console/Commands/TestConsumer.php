<?php

namespace Junges\Kafka\Tests\Integration\Console\Commands;

use Junges\Kafka\Contracts\Consumer;
use RdKafka\Message;

class TestConsumer extends Consumer
{
    public static Message $message;

    public function handle(Message $message): void
    {
        self::$message = $message;
    }
}
