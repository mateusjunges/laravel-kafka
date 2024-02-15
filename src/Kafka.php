<?php

namespace Junges\Kafka;

use Illuminate\Support\Traits\Macroable;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Contracts\CanConsumeMessagesFromKafka;
use Junges\Kafka\Contracts\CanProduceMessages;
use Junges\Kafka\Contracts\CanPublishMessagesToKafka;
use Junges\Kafka\Producers\ProducerBuilder;

class Kafka implements CanPublishMessagesToKafka, CanConsumeMessagesFromKafka
{
    use Macroable;

    private bool $shouldFake = false;

    /**
     * Creates a new ProducerBuilder instance, setting brokers and topic.
     *
     * @param string|null $broker
     * @param string $topic
     * @return CanProduceMessages
     */
    public function publishOn(string $topic, string $broker = null): CanProduceMessages
    {
        if ($this->shouldFake) {
            return Facades\Kafka::fake()->publishOn($topic, $broker);
        }

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
        if ($this->shouldFake) {
            return Facades\Kafka::fake()->createConsumer(
                $topics,
                $groupId,
                $brokers
            );
        }

        return ConsumerBuilder::create(
            brokers: $brokers ?? config('kafka.brokers'),
            topics: $topics,
            groupId: $groupId ?? config('kafka.consumer_group_id')
        );
    }
    
    public function shouldFake(): self
    {
        $this->shouldFake = true;

        return $this;
    }
}
