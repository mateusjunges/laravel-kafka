<?php declare(strict_types=1);

namespace Junges\Kafka;

use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Contracts\ConsumeMessagesFromKafka;
use Junges\Kafka\Contracts\MessageProducer;
use Junges\Kafka\Contracts\MessagePublisher;
use Junges\Kafka\Producers\ProducerBuilder;

class Kafka implements MessagePublisher, ConsumeMessagesFromKafka
{
    /** Creates a new ProducerBuilder instance, setting brokers and topic. */
    public function publishOn(string $topic, string $broker = null): MessageProducer
    {
        return new ProducerBuilder(
            topic: $topic,
            broker: $broker ?? config('kafka.brokers')
        );
    }

    /** Return a ConsumerBuilder instance.  */
    public function createConsumer(array $topics = [], string $groupId = null, string $brokers = null): ConsumerBuilder
    {
        return ConsumerBuilder::create(
            brokers: $brokers ?? config('kafka.brokers'),
            topics: $topics,
            groupId: $groupId ?? config('kafka.consumer_group_id')
        );
    }
}
