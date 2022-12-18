<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface ConsumeMessagesFromKafka
{
    /** Return a ConsumerBuilder instance. */
    public function createConsumer(array $topics = [], string $groupId = null, string $brokers = null): ConsumerBuilder;
}
