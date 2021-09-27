<?php

namespace Junges\Kafka\Message\Decoders;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Contracts\MessageDecoder;
use Junges\Kafka\Message\ConsumedMessage;

class JsonDecoder implements MessageDecoder
{
    /**
     * @param KafkaConsumerMessage $message
     * @return KafkaConsumerMessage
     * @throws \JsonException
     */
    public function decode(KafkaConsumerMessage $message): KafkaConsumerMessage
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
