<?php

namespace Junges\Kafka\Contracts;


interface CanConsumeMessagesFromKafka
{
    /**
     * @param string $brokers
     * @param array $topics
     * @param string|null $groupId
     * @return ConsumerBuilder
     */
    public function createConsumer(string $brokers, array $topics = [], string $groupId = null): ConsumerBuilder;

    /**
     * @param string $consumer
     * @return ConsumerBuilder
     */
    public function consumeUsing(string $consumer): ConsumerBuilder;
}
