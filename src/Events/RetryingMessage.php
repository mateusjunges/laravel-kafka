<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Throwable;

/**
 * Dispatched when the handler failed and the message will be retried, after the backoff. The message
 * holds the number of the attempt that failed.
 */
final readonly class RetryingMessage
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
