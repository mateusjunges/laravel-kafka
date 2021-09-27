<?php

namespace Junges\Kafka\Contracts;

use AvroSchema;

interface KafkaAvroSchemaRegistry
{
    public const LATEST_VERSION = -1;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return int
     */
    public function getVersion(): int;

    /**
     * @param AvroSchema $definition
     * @return void
     */
    public function setDefinition(AvroSchema $definition): void;

    /**
     * @return AvroSchema|null
     */
    public function getDefinition(): ?AvroSchema;
}
