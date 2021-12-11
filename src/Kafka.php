<?php

namespace Junges\Kafka;

use InvalidArgumentException;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use Junges\Kafka\Producers\ProducerBuilder;

class Kafka implements CanPublishMessagesToKafka
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
            topic: $topic,
            broker: $broker ?? config('kafka.brokers')
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
        return ConsumerBuilder::create(
            brokers: $brokers ?? config('kafka.brokers'),
            topics: $topics,
            groupId: $groupId ?? config('kafka.consumer_group_id')
        );
    }

    /**
     * Creates a new ConsumerBuilder instance based on pre-defined configuration.
     *
     * @param string $consumer
     * @return ConsumerBuilder
     */
    public function consumeUsing(string $consumer): ConsumerBuilder
    {
        $consumerConfig = config('kafka.consumers.'.$consumer);

        if ($consumerConfig === null) {
            throw new InvalidArgumentException("THe consumer [{$consumer}] is not defined.");
        }

        return ConsumerBuilder::createFromConsumerConfig($consumerConfig);
    }
}
