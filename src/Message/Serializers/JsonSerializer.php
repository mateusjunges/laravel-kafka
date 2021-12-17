<?php

namespace Junges\Kafka\Message\Serializers;

use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\MessageSerializer;

class JsonSerializer implements MessageSerializer
{
    /**
     * @param \Junges\Kafka\Contracts\KafkaProducerMessage $message
     * @return \Junges\Kafka\Contracts\KafkaProducerMessage
     * @throws \JsonException
     */
    public function serialize(KafkaProducerMessage $message): KafkaProducerMessage
    {
        $body = json_encode($message->getBody(), JSON_THROW_ON_ERROR);

        return $message->withBody($body);
    }
}
