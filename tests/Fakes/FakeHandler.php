<?php

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class FakeHandler extends Handler
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
