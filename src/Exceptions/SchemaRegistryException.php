<?php

namespace Junges\Kafka\Exceptions;

use RuntimeException;

class SchemaRegistryException extends RuntimeException
{
    public const SCHEMA_MAPPING_NOT_FOUND = 'There is no schema mapping topic: %s, type: %s';
}
