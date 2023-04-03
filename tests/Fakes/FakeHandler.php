<?php

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class FakeHandler extends Consumer
{
    /**
     * @var \Junges\Kafka\Contracts\KafkaConsumerMessage|null
     */
    private $lastMessage;

    public function lastMessage(): ?KafkaConsumerMessage
    {
        return $this->lastMessage;
    }

    public function handle(KafkaConsumerMessage $message): void
    {
        $this->lastMessage = $message;
    }
}
