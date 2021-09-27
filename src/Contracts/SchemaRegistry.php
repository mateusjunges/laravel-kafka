<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Exceptions\SchemaRegistryException;

interface SchemaRegistry
{
    /** @var string */
    public const BODY_IDX = 'body';

    /** @var string */
    public const KEY_IDX = 'key';

    /**
     * @param string                   $topicName
     * @param SchemaRegistry $avroSchema
     * @return void
     */
    public function addBodySchemaMappingForTopic(string $topicName, SchemaRegistry $avroSchema): void;

    /**
     * @param string                   $topicName
     * @param SchemaRegistry $avroSchema
     * @return void
     */
    public function addKeySchemaMappingForTopic(string $topicName, SchemaRegistry $avroSchema): void;

    /**
     * @return array<string, SchemaRegistry[]>
     */
    public function getTopicSchemaMapping(): array;

    /**
     * @param string $topicName
     * @return SchemaRegistry
     * @throws SchemaRegistryException
     */
    public function getBodySchemaForTopic(string $topicName): SchemaRegistry;

    /**
     * @param string $topicName
     * @return SchemaRegistry
     * @throws SchemaRegistryException
     */
    public function getKeySchemaForTopic(string $topicName): SchemaRegistry;

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