<?php

namespace Junges\Kafka\Contracts;

use Throwable;

abstract class Consumer
{
    abstract public function handle(KafkaConsumerMessage $message): void;

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
