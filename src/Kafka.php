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
     * @param string $cluster
     * @return \Junges\Kafka\Contracts\CanProduceMessages
     */
    public function publishOn(string $cluster): CanProduceMessages
    {
        $clusterConfig = config('kafka.clusters.'.$cluster);

        if ($clusterConfig === null) {
            throw new InvalidArgumentException("Cluster [{$cluster}] is not defined.");
        }

        return ProducerBuilder::create($clusterConfig);
    }

    /**
     * Return a ConsumerBuilder instance.
     *
     * @param string $brokers
     * @param array $topics
     * @param ?string $groupId
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

    /**
     * Creates a new ConsumerBuilder instance based on pre-defined configuration.
     *
     * @param string $consumer
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function consumeUsing(string $consumer): ConsumerBuilder
    {
        $consumerConfig = config('kafka.consumers.'.$consumer);

        if ($consumerConfig === null) {
            throw new InvalidArgumentException("Consumer [{$consumer}] is not defined.");
        }

        return ConsumerBuilder::createFromConsumerConfig($consumerConfig);
    }
}
