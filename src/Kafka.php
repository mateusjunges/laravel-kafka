<?php

namespace Junges\Kafka;

use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use Junges\Kafka\Producers\ProducerBuilder;

class Kafka implements CanPublishMessagesToKafka
{
    /**
     * @param string $broker
     * @param string $topic
     * @return CanProduceMessages
     */
    public function publishOn(string $broker, string $topic): CanProduceMessages
    {
        return new ProducerBuilder(
            broker: $broker,
            topic: $topic
        );
    }
}