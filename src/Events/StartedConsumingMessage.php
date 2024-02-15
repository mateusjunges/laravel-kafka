<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\ConsumerMessage;

final readonly class StartedConsumingMessage
{
    public function __construct(
        public readonly ConsumerMessage $message,
    ) {
    }

    public function getMessageIdentifier(): string
    {
        return $this->message->getMessageIdentifier();
    }
}
