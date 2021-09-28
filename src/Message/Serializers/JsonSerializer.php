<?php

namespace Junges\Kafka\Message\Serializers;

use JsonException;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;

class JsonSerializer implements MessageSerializer
{
    /**
     * @param KafkaProducerMessage $message
     * @return KafkaProducerMessage
     * @throws JsonException
     */
    public function serialize(KafkaProducerMessage $message): KafkaProducerMessage
    {
        $body = json_encode($message->getBody(), JSON_THROW_ON_ERROR);

        return $message->withBody($body);
    }
}
