<?php

namespace Junges\Kafka\Contracts;

interface CanPublishMessagesToKafka
{
    /**
     * Creates a new ProducerBuilder instance, setting brokers and topic.
     *
     * @param string|null $broker
     * @param string $topic
     * @return CanProduceMessages
     */
    public function publishOn(string $topic, string $broker = null): CanProduceMessages;
}
