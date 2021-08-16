<?php

namespace Junges\Kafka;

use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use PHPUnit\Framework\Assert as PHPUnit;

class KafkaFake implements CanPublishMessagesToKafka
{
    private array $messages = [];

    public function publish(Message $message, string $topic, $key = null): KafkaFake
    {
        $this->messages[$topic] = $message;

        return $this;
    }

    public function assertPublished(Message $message)
    {
        PHPUnit::assertContains($message, $this->messages);
    }

    public function assertPublishedOnTopic(Message $message, string $topic)
    {
        PHPUnit::assertEquals($message, $this->messages[$topic]);
    }
}