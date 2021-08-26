<?php

namespace Junges\Kafka\Contracts;

use RdKafka\Message;
use Throwable;

abstract class Consumer
{
    public abstract function handle(Message $message): void;

    /**
     * @throws Throwable
     */
    public function failed(string $message, string $topic, Throwable $exception): void
    {
        throw $exception;
    }

    public function producerKey(string $message): ?string
    {
        return null;
    }
}