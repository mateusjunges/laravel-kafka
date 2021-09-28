<?php

namespace Junges\Kafka\Contracts;

interface AvroMessageSerializer extends MessageSerializer
{
    public function getRegistry(): AvroSchemaRegistry;
}
