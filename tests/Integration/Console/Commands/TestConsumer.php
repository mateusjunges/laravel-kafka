<?php

namespace Junges\Kafka\Tests\Integration\Console\Commands;

use Junges\Kafka\Contracts\Consumer;
use RdKafka\Message;

class TestConsumer extends Consumer
{
    public static $message;

    public function handle(Message $message): void
    {
        self::$message = $message;
        return;
    }
}