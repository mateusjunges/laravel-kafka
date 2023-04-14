<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

final class MessageSentToDLQ
{
    public function __construct(
        public readonly string $payload,
        public readonly ?string $key,
        public readonly array $headers,
        public readonly ?\Throwable $throwable
    ) {}
}