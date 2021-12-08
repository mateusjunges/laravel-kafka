<?php

namespace Junges\Kafka\Exceptions;

class SchemaRegistryException extends LaravelKafkaException
{
    public const SCHEMA_MAPPING_NOT_FOUND = 'There is no schema mapping topic: %s, type: %s';
}
