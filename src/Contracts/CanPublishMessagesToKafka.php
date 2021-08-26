<?php

namespace Junges\Kafka\Contracts;

interface CanPublishMessagesToKafka
{
    public function publishOn(string $broker, string $topic): CanProduceMessages;
}
