<?php

namespace Junges\Kafka\Contracts;

interface MessageSerializer
{
    public function serialize(KafkaProducerMessage $message): KafkaProducerMessage;
}
