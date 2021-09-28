<?php

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class FakeHandler extends Consumer
{
    private ?KafkaConsumerMessage $lastMessage = null;

    public function lastMessage(): ?KafkaConsumerMessage
    {
        return $this->lastMessage;
    }

    public function handle(KafkaConsumerMessage $message): void
    {
        $this->lastMessage = $message;
    }
}
