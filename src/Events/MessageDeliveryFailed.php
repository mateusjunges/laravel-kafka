<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

/**
 * Dispatched when a published message could not be delivered to Kafka, for instance because its topic
 * does not exist or it was not acknowledged within "message.timeout.ms". Messages are delivered in the
 * background, so this is the only way to know that a message queued by publish() was not delivered.
 */
final readonly class MessageDeliveryFailed
{
    public function __construct(
        public string $topic,
        public int $partition,
        public ?string $key,
        public ?string $payload,
        public array $headers,
        public int $errorCode,
        public string $error,
        public ?string $messageIdentifier,
        /** The name of the connection the message was published on. */
        public ?string $connection = null,
    ) {}

    public function getMessageIdentifier(): ?string
    {
        return $this->messageIdentifier;
    }
}
