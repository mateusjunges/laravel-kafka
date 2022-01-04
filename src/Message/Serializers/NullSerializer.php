<?php

namespace Junges\Kafka\Message\Serializers;

use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;

class NullSerializer implements MessageSerializer
{
    /**
     * Serializes the message.
     *
     * @param KafkaProducerMessage $message
     * @return KafkaProducerMessage
     */
    public function serialize(KafkaProducerMessage $message): KafkaProducerMessage
    {
        return $message;
    }
}
