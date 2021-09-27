<?php

namespace Junges\Kafka\Contracts;

interface AvroMessageDecoder extends MessageDecoder
{
    public function getRegistry(): SchemaRegistry;
}
