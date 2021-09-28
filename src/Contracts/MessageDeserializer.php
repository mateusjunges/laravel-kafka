<?php

namespace Junges\Kafka\Contracts;

interface MessageDeserializer
{
    public function deserialize(KafkaConsumerMessage $message): KafkaConsumerMessage;
}
