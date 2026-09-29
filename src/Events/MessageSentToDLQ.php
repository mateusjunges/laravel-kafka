<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Throwable;

/**
 * Dispatched when a failed message was sent to the dead letter queue. The message is the one the handler last
 * received, with the topic, partition and offset it was consumed from, while the payload, key and headers are
 * the ones published to the dead letter queue.
 */
final readonly class MessageSentToDLQ
{
    public function __construct(
        public ConsumerMessage $message,
        public Throwable $throwable,
        public string $topic,
        public ?string $payload,
        public ?string $key,
        public array $headers,
        public Consumer $consumer,
    ) {}

    public function getMessageIdentifier(): string
    {
        return $this->message->getMessageIdentifier();
    }
}
