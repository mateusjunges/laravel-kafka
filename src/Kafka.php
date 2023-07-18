<?php

namespace Junges\Kafka;

use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Contracts\CanConsumeMessagesFromKafka;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use Junges\Kafka\Producers\ProducerBuilder;

class Kafka implements CanPublishMessagesToKafka, CanConsumeMessagesFromKafka
{
    /**
     * Creates a new ProducerBuilder instance, setting brokers and topic.
     *
     * @param string|null $broker
     * @param string $topic
     * @return CanProduceMessages
     */
    public function publishOn(string $topic, string $broker = null): CanProduceMessages
    {
        $defaultBrokers = config('kafka.broker_connections.' . config('kafka.default')) ?? config('kafka.brokers');

        return new ProducerBuilder(
            topic: $topic,
            broker: $broker ?? $defaultBrokers
        );
    }

    /**
     * Return a ConsumerBuilder instance.
     *
     * @param array $topics
     * @param string|null $groupId
     * @param string|null $brokers
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function createConsumer(array $topics = [], string $groupId = null, string $brokers = null): ConsumerBuilder
    {
        $defaultBrokers = config('kafka.broker_connections.' . config('kafka.default')) ?? config('kafka.brokers');

        return ConsumerBuilder::create(
            brokers: $brokers ?? $defaultBrokers,
            topics: $topics,
            groupId: $groupId ?? config('kafka.consumer_group_id')
        );
    }
}
