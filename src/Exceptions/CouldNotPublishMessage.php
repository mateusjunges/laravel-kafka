<?php

namespace Junges\Kafka\Exceptions;

use JetBrains\PhpStorm\Pure;

class CouldNotPublishMessage extends LaravelKafkaException
{
    public static function flushError(string $message = "Sent messages may not be completed yet."): self
    {
        return new static($message);
    }

    public static function withMessage(string $message, int $code): self
    {
        return new static("Your message could not be published. Flush returned with error code $code: '$message'");
    }
}
