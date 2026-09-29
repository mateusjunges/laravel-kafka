<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

/** Dispatched when Kafka acknowledged a published message. */
final readonly class MessageDelivered
{
    public function __construct(
        public string $topic,
        public int $partition,
        public int $offset,
        public ?string $key,
        public ?string $messageIdentifier,
        /** The name of the connection the message was published on. */
        public ?string $connection = null,
    ) {}

    public function getMessageIdentifier(): ?string
    {
        return $this->messageIdentifier;
    }
}
