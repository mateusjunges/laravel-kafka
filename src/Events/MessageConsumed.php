<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;

/** Dispatched when the handler processed a message. The message holds the number of the attempt that succeeded. */
final readonly class MessageConsumed
{
    public function __construct(
        public ConsumerMessage $message,
        public Consumer $consumer,
    ) {}

    public function getMessageIdentifier(): string
    {
        return $this->message->getMessageIdentifier();
    }
}
