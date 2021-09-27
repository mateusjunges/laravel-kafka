<?php

namespace Junges\Kafka\Message\Encoders;

use JsonException;
use Junges\Kafka\Contracts\MessageEncoder;
use Junges\Kafka\Contracts\KafkaProducerMessage;

class JsonEncoder implements MessageEncoder
{
    /**
     * @param KafkaProducerMessage $message
     * @return KafkaProducerMessage
     * @throws JsonException
     */
    public function encode(KafkaProducerMessage $message): KafkaProducerMessage
    {
        $body = json_encode($message->getBody(), JSON_THROW_ON_ERROR);

        return $message->withBody($body);
    }
}