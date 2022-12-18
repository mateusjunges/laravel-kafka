<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions\Serializers;

use Junges\Kafka\Exceptions\LaravelKafkaException;

class AvroSerializerException extends LaravelKafkaException
{
    final public const NO_SCHEMA_FOR_TOPIC_MESSAGE = 'There is no %s avro schema defined for the topic %s';
    final public const UNABLE_TO_LOAD_DEFINITION_MESSAGE = 'Was unable to load definition for schema %s';
}
