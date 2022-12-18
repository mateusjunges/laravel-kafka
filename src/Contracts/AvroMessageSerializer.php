<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface AvroMessageSerializer extends MessageSerializer
{
    public function getRegistry(): AvroSchemaRegistry;
}
