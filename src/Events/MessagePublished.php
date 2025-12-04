<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\ProducerMessage;

final readonly class MessagePublished
{
    public function __construct(
        public ProducerMessage $message,
    ) {}

    public function getMessageIdentifier(): string
    {
        return $this->message->getMessageIdentifier();
    }
}
