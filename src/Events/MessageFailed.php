<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Throwable;

/**
 * Dispatched when a message is handled as failed, once its retries are used, before it is sent to the dead
 * letter queue, skipped, or stops the consumer. A message that can't be deserialized fails right away, and
 * is then received with its raw body.
 */
final readonly class MessageFailed
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
