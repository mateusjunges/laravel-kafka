<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface ConsumeMessagesFromKafka
{
    /**
     * Return a ConsumerBuilder instance.
     *
     * @param string|null $groupId
     * @param string|null $brokers
     * @return \Junges\Kafka\Consumers\ConsumerBuilder
     */
    public function createConsumer(array $topics = [], string $groupId = null, string $brokers = null): ConsumerBuilder;
}
