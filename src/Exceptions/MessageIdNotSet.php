<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions;

final class MessageIdNotSet extends LaravelKafkaException
{
    public function __construct(
        string $message = "The message identifier was not set.",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
