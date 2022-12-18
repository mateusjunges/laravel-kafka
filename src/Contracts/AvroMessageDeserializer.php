<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface AvroMessageDeserializer extends MessageDeserializer
{
    public function getRegistry(): AvroSchemaRegistry;
}
