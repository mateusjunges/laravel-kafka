<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface CanPublishMessagesToKafka
{
    /**
     * Creates a new ProducerBuilder instance, setting brokers and topic.
     *
     * @param string|null $broker
     */
    public function publishOn(string $topic, string $broker = null): CanProduceMessages;
}
