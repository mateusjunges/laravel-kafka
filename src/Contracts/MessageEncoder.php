<?php

namespace Junges\Kafka\Contracts;

interface MessageEncoder
{
    public function encode(KafkaProducerMessage $message): KafkaProducerMessage;
}
