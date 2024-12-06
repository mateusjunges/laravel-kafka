<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

final readonly class RunningOnStopConsumingCallbacks
{
    public function __construct(public string $identifier)
    {
    }
}