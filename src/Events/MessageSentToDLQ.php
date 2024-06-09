<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

final readonly class MessageSentToDLQ
{
    public function __construct(
        public ?string $payload,
        public ?string $key,
        public array $headers,
        public ?\Throwable $throwable,
        public ?string $messageIdentifier,
    ) {
    }

    public function getMessageIdentifier(): ?string
    {
        return $this->messageIdentifier;
    }
}
