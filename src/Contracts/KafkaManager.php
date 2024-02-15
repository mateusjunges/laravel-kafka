<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface KafkaManager
{
    /** Creates a new ProducerBuilder instance, setting brokers and topic. */
    public function publish(?string $broker = null): MessageProducer;

    /** Return a ConsumerBuilder instance. */
    public function consumer(array $topics = [], string $groupId = null, string $brokers = null): ConsumerBuilder;

    public function shouldFake(): self;
}
