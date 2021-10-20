<?php

namespace Junges\Kafka;

use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use Junges\Kafka\Producers\ProducerBuilder;

class Kafka implements CanPublishMessagesToKafka
{
    /**
     * Creates a new ProducerBuilder instance, setting brokers and topic.
     *
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

    /**
     * Return a ConsumerBuilder instance.
     *
     * @param string $brokers
     * @param array $topics
     * @param string|null $groupId
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function createConsumer(string $brokers, array $topics = [], string $groupId = null): ConsumerBuilder
    {
        return ConsumerBuilder::create(
            brokers: $brokers,
            topics: $topics,
            groupId: $groupId
        );
    }
}
