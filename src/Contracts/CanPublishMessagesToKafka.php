<?php

namespace Junges\Kafka\Contracts;

interface CanPublishMessagesToKafka
{
    public function publishOn(string $topic, string $broker = null): CanProduceMessages;
}
