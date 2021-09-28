<?php

namespace Junges\Kafka\Message\Serializers;

use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;

class NullSerializer implements MessageSerializer
{
    public function serialize(KafkaProducerMessage $message): KafkaProducerMessage
    {
        return $message;
    }
}
