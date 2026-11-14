<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Consumers\StopReason;
use Junges\Kafka\Contracts\Consumer;
use Throwable;

/**
 * Dispatched when a consumer stopped consuming and left its consumer group, whether it stopped normally or
 * because of an exception, which is thrown by consume() right after this event.
 */
final readonly class ConsumerStopped
{
    public function __construct(
        public Consumer $consumer,
        public StopReason $reason,
        public ?Throwable $exception = null,
    ) {}
}
