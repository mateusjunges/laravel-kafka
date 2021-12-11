<?php

namespace Junges\Kafka\Message\Deserializers;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Contracts\MessageDeserializer;

class NullDeserializer implements MessageDeserializer
{
    /**
     * Deserializes the message.
     *
     * @param KafkaConsumerMessage $message
     * @return KafkaConsumerMessage
     */
    public function deserialize(KafkaConsumerMessage $message): KafkaConsumerMessage
    {
        return $message;
    }
}
