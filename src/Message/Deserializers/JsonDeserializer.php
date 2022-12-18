<?php declare(strict_types=1);

namespace Junges\Kafka\Message\Deserializers;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Message\ConsumedMessage;

class JsonDeserializer implements MessageDeserializer
{
    /** @throws \JsonException  */
    public function deserialize(ConsumerMessage $message): ConsumerMessage
    {
        $body = json_decode((string) $message->getBody(), true, 512, JSON_THROW_ON_ERROR);

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
