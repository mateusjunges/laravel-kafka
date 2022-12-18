<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface MessagePublisher
{
    /** Creates a new ProducerBuilder instance, setting brokers and topic. */
    public function publishOn(string $topic, string $broker = null): MessageProducer;
}
