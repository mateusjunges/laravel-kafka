<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

final readonly class ConsumerStopped
{
    public function __construct(public string $identifier)
    {
    }
}