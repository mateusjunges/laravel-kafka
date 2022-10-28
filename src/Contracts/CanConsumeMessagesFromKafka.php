<?php

namespace Junges\Kafka\Contracts;

interface CanConsumeMessagesFromKafka
{
    /**
     * Return a ConsumerBuilder instance.
     *
     * @param array $topics
     * @param string|null $groupId
     * @param string|null $brokers
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function createConsumer(array $topics = [], string $groupId = null, string $brokers = null): ConsumerBuilder;
}
