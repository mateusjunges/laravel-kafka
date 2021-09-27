<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Exceptions\SchemaRegistryException;

interface AvroSchemaRegistry
{
    /** @var string */
    public const BODY_IDX = 'body';

    /** @var string */
    public const KEY_IDX = 'key';

    /**
     * @param string                   $topicName
     * @param AvroSchemaRegistry $avroSchema
     * @return void
     */
    public function addBodySchemaMappingForTopic(string $topicName, AvroSchemaRegistry $avroSchema): void;

    /**
     * @param string                   $topicName
     * @param AvroSchemaRegistry $avroSchema
     * @return void
     */
    public function addKeySchemaMappingForTopic(string $topicName, AvroSchemaRegistry $avroSchema): void;

    /**
     * @return array<string, AvroSchemaRegistry[]>
     */
    public function getTopicSchemaMapping(): array;

    /**
     * @param string $topicName
     * @return KafkaAvroSchemaRegistry
     * @throws SchemaRegistryException
     */
    public function getBodySchemaForTopic(string $topicName): KafkaAvroSchemaRegistry;

    /**
     * @param string $topicName
     * @return KafkaAvroSchemaRegistry
     * @throws SchemaRegistryException
     */
    public function getKeySchemaForTopic(string $topicName): KafkaAvroSchemaRegistry;

    /**
     * @param string $topicName
     * @return boolean
     * @throws SchemaRegistryException
     */
    public function hasBodySchemaForTopic(string $topicName): bool;

    /**
     * @param string $topicName
     * @return boolean
     * @throws SchemaRegistryException
     */
    public function hasKeySchemaForTopic(string $topicName): bool;
}