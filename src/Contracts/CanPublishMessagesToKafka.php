<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Producers\ProducerBuilder;

interface CanPublishMessagesToKafka
{
    public function publishOn(string $broker, string $topic): CanProduceMessages;
}