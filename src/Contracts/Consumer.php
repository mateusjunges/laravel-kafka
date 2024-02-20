<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use RdKafka\Message;
use Throwable;

abstract class Consumer
{
    abstract public function handle(ConsumerMessage $message, MessageConsumer $consumer): void;

    /** @throws Throwable  */
    public function failed(string $message, string $topic, Throwable $exception): never
    {
        throw $exception;
    }

    public function producerKey(Message $message): ?string
    {
        return $message->key ?? null;
    }
}
