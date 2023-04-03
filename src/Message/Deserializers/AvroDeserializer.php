<?php

namespace Junges\Kafka\Message\Deserializers;

use FlixTech\AvroSerializer\Objects\RecordSerializer;
use Junges\Kafka\Contracts\AvroMessageDeserializer;
use Junges\Kafka\Contracts\AvroSchemaRegistry;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Message\ConsumedMessage;

class AvroDeserializer implements AvroMessageDeserializer
{
    /**
     * @var \Junges\Kafka\Contracts\AvroSchemaRegistry
     */
    private $registry;
    /**
     * @var \FlixTech\AvroSerializer\Objects\RecordSerializer
     */
    private $recordSerializer;
    public function __construct(AvroSchemaRegistry $registry, RecordSerializer   $recordSerializer)
    {
        $this->registry = $registry;
        $this->recordSerializer = $recordSerializer;
    }

    public function getRegistry(): AvroSchemaRegistry
    {
        return $this->registry;
    }

    public function deserialize(KafkaConsumerMessage $message): KafkaConsumerMessage
    {
        return new ConsumedMessage(
            $message->getTopicName(),
            $message->getPartition(),
            $message->getHeaders(),
            $this->decodeBody($message),
            $this->decodeKey($message),
            $message->getOffset(),
            $message->getTimestamp()
        );
    }

    private function decodeBody(KafkaConsumerMessage $message)
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

    private function decodeKey(KafkaConsumerMessage $message)
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
