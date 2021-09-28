<?php

namespace Junges\Kafka\Contracts;

interface AvroMessageDeserializer extends MessageDeserializer
{
    public function getRegistry(): AvroSchemaRegistry;
}
