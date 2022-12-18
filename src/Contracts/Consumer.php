<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use Throwable;

abstract class Consumer
{
    abstract public function handle(ConsumerMessage $message): void;

    /**
     * @throws Throwable
     */
    public function failed(string $message, string $topic, Throwable $exception): never
    {
        throw $exception;
    }

    public function producerKey(string $message): ?string
    {
        return null;
    }
}
