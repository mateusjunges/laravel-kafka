<?php declare(strict_types=1);

namespace Junges\Kafka\Message\Registry;

use AvroSchema;
use FlixTech\SchemaRegistryApi\Registry;
use Junges\Kafka\Contracts\AvroSchemaRegistry as AvroSchemaRegistryContract;
use Junges\Kafka\Contracts\KafkaAvroSchemaRegistry;
use Junges\Kafka\Exceptions\SchemaRegistryException;

class AvroSchemaRegistry implements AvroSchemaRegistryContract
{
    /** @var array<string, AvroSchemaRegistry[]> */
    private array $schemaMapping = [
        self::BODY_IDX => [],
        self::KEY_IDX => [],
    ];

    /** AvroSchemaRegistry constructor. */
    public function __construct(private readonly Registry $registry)
    {
    }

    public function addBodySchemaMappingForTopic(string $topicName, KafkaAvroSchemaRegistry $avroSchema): void
    {
        $this->schemaMapping[self::BODY_IDX][$topicName] = $avroSchema;
    }

    public function addKeySchemaMappingForTopic(string $topicName, KafkaAvroSchemaRegistry $avroSchema): void
    {
        $this->schemaMapping[self::KEY_IDX][$topicName] = $avroSchema;
    }

    public function getBodySchemaForTopic(string $topicName): KafkaAvroSchemaRegistry
    {
        return $this->getSchemaForTopicAndType($topicName, self::BODY_IDX);
    }

    public function getKeySchemaForTopic(string $topicName): KafkaAvroSchemaRegistry
    {
        return $this->getSchemaForTopicAndType($topicName, self::KEY_IDX);
    }

    public function hasBodySchemaForTopic(string $topicName): bool
    {
        return isset($this->schemaMapping[self::BODY_IDX][$topicName]);
    }

    public function hasKeySchemaForTopic(string $topicName): bool
    {
        return isset($this->schemaMapping[self::KEY_IDX][$topicName]);
    }

    private function getSchemaForTopicAndType(string $topicName, string $type): KafkaAvroSchemaRegistry
    {
        if (false === isset($this->schemaMapping[$type][$topicName])) {
            throw new SchemaRegistryException(
                sprintf(
                    SchemaRegistryException::SCHEMA_MAPPING_NOT_FOUND,
                    $topicName,
                    $type
                )
            );
        }

        $avroSchema = $this->schemaMapping[$type][$topicName];

        if (null !== $avroSchema->getDefinition()) {
            return $avroSchema;
        }

        $avroSchema->setDefinition($this->getSchemaDefinition($avroSchema));
        $this->schemaMapping[$type][$topicName] = $avroSchema;

        return $avroSchema;
    }

    private function getSchemaDefinition(KafkaAvroSchemaRegistry $avroSchema): AvroSchema
    {
        if (KafkaAvroSchemaRegistry::LATEST_VERSION === $avroSchema->getVersion()) {
            return $this->registry->latestVersion($avroSchema->getName());
        }

        return $this->registry->schemaForSubjectAndVersion($avroSchema->getName(), $avroSchema->getVersion());
    }

    /** @return array<string, AvroSchemaRegistry[]>  */
    public function getTopicSchemaMapping(): array
    {
        return $this->schemaMapping;
    }
}
