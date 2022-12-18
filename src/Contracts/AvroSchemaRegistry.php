<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Exceptions\SchemaRegistryException;

interface AvroSchemaRegistry
{
    /** @var string */
    public const BODY_IDX = 'body';

    /** @var string */
    public const KEY_IDX = 'key';

    public function addBodySchemaMappingForTopic(string $topicName, KafkaAvroSchemaRegistry $avroSchema): void;

    public function addKeySchemaMappingForTopic(string $topicName, KafkaAvroSchemaRegistry $avroSchema): void;

    /** @return array<string, AvroSchemaRegistry[]>  */
    public function getTopicSchemaMapping(): array;

    /** @throws SchemaRegistryException  */
    public function getBodySchemaForTopic(string $topicName): KafkaAvroSchemaRegistry;

    /** @throws SchemaRegistryException */
    public function getKeySchemaForTopic(string $topicName): KafkaAvroSchemaRegistry;

    /** @throws SchemaRegistryException */
    public function hasBodySchemaForTopic(string $topicName): bool;

    /** @throws SchemaRegistryException */
    public function hasKeySchemaForTopic(string $topicName): bool;
}
