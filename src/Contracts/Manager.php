<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface Manager
{
    /** Returns a new fresh instance of the Manager. */
    public function fresh(): self;

    /** Creates a new ProducerBuilder instance, setting brokers and topic. */
    public function publish(?string $broker = null): MessageProducer;

    /** Return a ConsumerBuilder instance. */
    public function consumer(array $topics = [], string $groupId = null, string $brokers = null): ConsumerBuilder;

    public function shouldFake(): self;

    /** @param array<int, ConsumerMessage> $messages */
    public function shouldReceiveMessages(array $messages): self;
}
