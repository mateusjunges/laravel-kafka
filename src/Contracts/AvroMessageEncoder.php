<?php

namespace Junges\Kafka\Contracts;

interface AvroMessageEncoder extends MessageEncoder
{
    public function getRegistry(): AvroSchemaRegistry;
}
