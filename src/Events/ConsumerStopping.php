<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

final readonly class ConsumerStopping
{
    public function __construct(public string $identifier)
    {
    }
}