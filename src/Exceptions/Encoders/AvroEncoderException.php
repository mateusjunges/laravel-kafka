<?php

namespace Junges\Kafka\Exceptions\Encoders;

use RuntimeException;

class AvroEncoderException extends RuntimeException
{
    public const NO_SCHEMA_FOR_TOPIC_MESSAGE = 'There is no %s avro schema defined for the topic %s';
    public const UNABLE_TO_LOAD_DEFINITION_MESSAGE = 'Was unable to load definition for schema %s';
}
