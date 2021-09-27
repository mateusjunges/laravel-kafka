<?php

namespace Junges\Kafka\Message\Encoders;

use AvroSchema;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use Junges\Kafka\Contracts\AvroMessageEncoder;
use Junges\Kafka\Contracts\KafkaAvroSchemaRegistry;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Contracts\AvroSchemaRegistry;
use Junges\Kafka\Exceptions\Encoders\AvroEncoderException;

class AvroEncoder implements AvroMessageEncoder
{
    public function __construct(
        private AvroSchemaRegistry $registry,
        private RecordSerializer   $recordSerializer
    ) {
    }

    public function getRegistry(): AvroSchemaRegistry
    {
        return $this->registry;
    }

    public function encode(KafkaProducerMessage $message): KafkaProducerMessage
    {
        $message = $this->encodeBody($message);

        return $this->encodeKey($message);
    }

    private function encodeBody(KafkaProducerMessage $producerMessage): KafkaProducerMessage
    {
        $topicName = $producerMessage->getTopicName();
        $body = $producerMessage->getBody();

        if (null === $body) {
            return $producerMessage;
        }

        if (false === $this->registry->hasBodySchemaForTopic($topicName)) {
            return $producerMessage;
        }

        $avroSchema = $this->registry->getBodySchemaForTopic($topicName);

        $encodedBody = $this->recordSerializer->encodeRecord(
            $avroSchema->getName(),
            $this->getAvroSchemaDefinition($avroSchema),
            $body
        );

        return $producerMessage->withBody($encodedBody);
    }

    private function encodeKey(KafkaProducerMessage $producerMessage): KafkaProducerMessage
    {
        $topicName = $producerMessage->getTopicName();
        $key = $producerMessage->getKey();

        if (null === $key) {
            return $producerMessage;
        }

        if (false === $this->registry->hasKeySchemaForTopic($topicName)) {
            return $producerMessage;
        }

        $avroSchema = $this->registry->getKeySchemaForTopic($topicName);

        $encodedKey = $this->recordSerializer->encodeRecord(
            $avroSchema->getName(),
            $this->getAvroSchemaDefinition($avroSchema),
            $key
        );

        return $producerMessage->withKey($encodedKey);
    }

    private function getAvroSchemaDefinition(KafkaAvroSchemaRegistry $avroSchema): AvroSchema
    {
        $schemaDefinition = $avroSchema->getDefinition();

        if (null === $schemaDefinition) {
            throw new AvroEncoderException(
                sprintf(
                    AvroEncoderException::UNABLE_TO_LOAD_DEFINITION_MESSAGE,
                    $avroSchema->getName()
                )
            );
        }

        return $schemaDefinition;
    }
}