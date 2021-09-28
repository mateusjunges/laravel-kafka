<?php

namespace Junges\Kafka\Message\Deserializers;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Contracts\MessageDeserializer;

class NullDeserializer implements MessageDeserializer
{
    public function deserialize(KafkaConsumerMessage $message): KafkaConsumerMessage
    {
        return $message;
    }
}
