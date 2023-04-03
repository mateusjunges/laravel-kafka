<?php

namespace Junges\Kafka\Message\Deserializers;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Message\ConsumedMessage;

class JsonDeserializer implements MessageDeserializer
{
    /**
     * @param KafkaConsumerMessage $message
     * @return KafkaConsumerMessage
     * @throws \JsonException
     */
    public function deserialize(KafkaConsumerMessage $message): KafkaConsumerMessage
    {
        $body = json_decode($message->getBody(), true, 512, 0);

        return new ConsumedMessage(
            $message->getTopicName(),
            $message->getPartition(),
            $message->getHeaders(),
            $body,
            $message->getKey(),
            $message->getOffset(),
            $message->getTimestamp()
        );
    }
}
