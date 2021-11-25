<?php

namespace Junges\Kafka\Exceptions;

use JetBrains\PhpStorm\Pure;

class CouldNotPublishMessage extends LaravelKafkaException
{
    #[Pure]
    public static function flushError(string $message = "Sent messages may not be completed yet."): self
    {
        return new static($message);
    }
}
