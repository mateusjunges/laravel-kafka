<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;

/** Dispatched when a message was received, before it is deserialized and handled. The message has its raw body. */
final readonly class StartedConsumingMessage
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
