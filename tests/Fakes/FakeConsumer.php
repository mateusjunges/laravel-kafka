<?php

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Contracts\KafkaConsumerMessage;

class FakeConsumer
{
    private KafkaConsumerMessage $message;

    public function __invoke(KafkaConsumerMessage $message)
    {
        $this->message = $message;
    }

    public function getMessage(): KafkaConsumerMessage
    {
        return $this->message;
    }
}
