<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions;

class CouldNotPublishMessage extends LaravelKafkaException
{
    /** Create the exception for a flush that failed with the given librdkafka error, available through getCode(). */
    public static function withMessage(string $message, int $code): self
    {
        return new static("Your message could not be published. Flush returned with error code $code: '$message'", $code);
    }
}
