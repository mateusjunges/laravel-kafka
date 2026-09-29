<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions;

class SchemaRegistryException extends LaravelKafkaException
{
    final public const string SCHEMA_MAPPING_NOT_FOUND = 'There is no schema mapping topic: %s, type: %s';
}
