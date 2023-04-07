<?php declare(strict_types=1);

namespace Junges\Kafka\Message;

use AvroSchema;
use Junges\Kafka\Contracts\KafkaAvroSchemaRegistry;

class KafkaAvroSchema implements KafkaAvroSchemaRegistry
{
    public function __construct(
        private readonly string $schemaName,
        private readonly int $version = KafkaAvroSchemaRegistry::LATEST_VERSION,
        private ?AvroSchema $definition = null
    ) {
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
