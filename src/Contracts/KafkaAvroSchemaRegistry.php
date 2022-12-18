<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use AvroSchema;

interface KafkaAvroSchemaRegistry
{
    public const LATEST_VERSION = -1;

    public function getName(): string;

    public function getVersion(): int;

    public function setDefinition(AvroSchema $definition): void;

    public function getDefinition(): ?AvroSchema;
}
