<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;

/** Dispatched when a consumer starts consuming, before it connects to Kafka. */
final readonly class ConsumerStarting
{
    public function __construct(
        public Consumer $consumer,
    ) {}
}
