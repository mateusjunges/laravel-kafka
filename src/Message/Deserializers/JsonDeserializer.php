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
        $body = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return new ConsumedMessage(
            topicName: $message->getTopicName(),
            partition: $message->getPartition(),
            headers: $message->getHeaders(),
            body: $body,
            key: $message->getKey(),
            offset: $message->getOffset(),
            timestamp: $message->getTimestamp()
        );
    }
}
