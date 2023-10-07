<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions;

use JetBrains\PhpStorm\Pure;

class CouldNotPublishMessage extends LaravelKafkaException
{
    private int $kafkaErrorCode;

    #[Pure]
    public static function flushError(string $message = "Sent messages may not be completed yet."): self
    {
        return new static($message);
    }

    public static function withMessage(string $message, int $code): self
    {
        $exception = new static("Your message could not be published. Flush returned with error code $code: '$message'");
        $exception->setErrorCode($code);

        return $exception;
    }

    public function setErrorCode(int $code): self
    {
        $this->kafkaErrorCode = $code;

        return $this;
    }

    public function getKafkaErrorCode(): int
    {
        return $this->kafkaErrorCode;
    }
}
