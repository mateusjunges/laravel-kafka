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
        return new ProducerBuilder(
            $topic,
            $broker ?? config('kafka.brokers')
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
    public function createConsumer(array $topics = [], string $groupId = null, string $brokers = null): \Junges\Kafka\Contracts\ConsumerBuilder
    {
        return ConsumerBuilder::create(
            $brokers ?? config('kafka.brokers'),
            $topics,
            $groupId ?? config('kafka.consumer_group_id')
        );
    }
}
