<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Throwable;

/** Dispatched when a message fails, there is no dead letter queue, and the consumer skips it. */
final readonly class MessageSkipped
{
    public function __construct(
        public ConsumerMessage $message,
        public Throwable $throwable,
        public Consumer $consumer,
    ) {}

    public function getMessageIdentifier(): string
    {
        return $this->message->getMessageIdentifier();
    }
}
