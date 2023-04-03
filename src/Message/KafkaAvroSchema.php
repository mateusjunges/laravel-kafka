<?php

namespace Junges\Kafka\Message;

use AvroSchema;
use Junges\Kafka\Contracts\KafkaAvroSchemaRegistry;

class KafkaAvroSchema implements KafkaAvroSchemaRegistry
{
    /**
     * @var string
     */
    private $schemaName;
    /**
     * @var int
     */
    private $version = KafkaAvroSchemaRegistry::LATEST_VERSION;
    /**
     * @var \AvroSchema|null
     */
    private $definition;
    public function __construct(string $schemaName, int $version = KafkaAvroSchemaRegistry::LATEST_VERSION, ?AvroSchema $definition = null)
    {
        $this->schemaName = $schemaName;
        $this->version = $version;
        $this->definition = $definition;
    }

    public function getName(): string
    {
        return $this->schemaName;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setDefinition(AvroSchema $definition): void
    {
        $this->definition = $definition;
    }

    public function getDefinition(): ?AvroSchema
    {
        return $this->definition;
    }
}
