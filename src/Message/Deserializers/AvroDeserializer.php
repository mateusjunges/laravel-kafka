<?php declare(strict_types=1);

namespace Junges\Kafka\Message\Deserializers;

use FlixTech\AvroSerializer\Objects\RecordSerializer;
use Junges\Kafka\Contracts\AvroMessageDeserializer;
use Junges\Kafka\Contracts\AvroSchemaRegistry;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Message\ConsumedMessage;

class AvroDeserializer implements AvroMessageDeserializer
{
    public function __construct(
        private readonly AvroSchemaRegistry $registry,
        private readonly RecordSerializer   $recordSerializer
    ) {
    }

    public function getRegistry(): AvroSchemaRegistry
    {
        return $this->registry;
    }

    public function deserialize(ConsumerMessage $message): ConsumerMessage
    {
        return new ConsumedMessage(
            topicName: $message->getTopicName(),
            partition: $message->getPartition(),
            headers: $message->getHeaders(),
            body: $this->decodeBody($message),
            key: $this->decodeKey($message),
            offset: $message->getOffset(),
            timestamp: $message->getTimestamp()
        );
    }

    private function decodeBody(ConsumerMessage $message)
    {
        $body = $message->getBody();
        $topicName = $message->getTopicName();

        if (null === $body) {
            return null;
        }

        if (false === $this->registry->hasBodySchemaForTopic($topicName)) {
            return $body;
        }

        $avroSchema = $this->registry->getBodySchemaForTopic($topicName);
        $schemaDefinition = $avroSchema->getDefinition();

        return $this->recordSerializer->decodeMessage($body, $schemaDefinition);
    }

    private function decodeKey(ConsumerMessage $message)
    {
        $key = $message->getKey();
        $topicName = $message->getTopicName();

        if (null === $key) {
            return null;
        }

        if (false === $this->registry->hasKeySchemaForTopic($topicName)) {
            return $key;
        }

        $avroSchema = $this->registry->getKeySchemaForTopic($topicName);
        $schemaDefinition = $avroSchema->getDefinition();

        return $this->recordSerializer->decodeMessage($key, $schemaDefinition);
    }
}
