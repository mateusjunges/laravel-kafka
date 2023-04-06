<?php

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Contracts\KafkaConsumerMessage;

final class FakeConsumer
{
    private KafkaConsumerMessage $message;

    public function __invoke(KafkaConsumerMessage $message): void
    {
        $this->message = $message;
    }

    public function getMessage(): KafkaConsumerMessage
    {
        return $this->message;
    }
}
