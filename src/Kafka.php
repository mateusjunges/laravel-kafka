<?php

namespace Junges\Kafka;

use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Contracts\CanConsumeMessagesFromKafka;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use Junges\Kafka\Producers\ProducerBuilder;

class Kafka implements CanPublishMessagesToKafka, CanConsumeMessagesFromKafka
{
    /** Creates a new ProducerBuilder instance, setting brokers and topic. */
    public function publishOn(string $topic, string $broker = null): CanProduceMessages
    {
        return new ProducerBuilder(
            topic: $topic,
            broker: $broker ?? config('kafka.brokers')
        );
    }

    /** Return a ConsumerBuilder instance. */
    public function createConsumer(array $topics = [], string $groupId = null, string $brokers = null): ConsumerBuilder
    {
        return ConsumerBuilder::create(
            brokers: $brokers ?? config('kafka.brokers'),
            topics: $topics,
            groupId: $groupId ?? config('kafka.consumer_group_id')
        );
    }
}
