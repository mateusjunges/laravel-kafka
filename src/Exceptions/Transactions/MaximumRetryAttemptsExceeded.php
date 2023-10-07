<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions\Transactions;

use Junges\Kafka\Exceptions\LaravelKafkaException;

final class MaximumRetryAttemptsExceeded extends LaravelKafkaException
{
    public static function new(int $maxRetryAttempts, \Throwable $previous): self
    {
        return new self(
            "Max retry attempts limit exceeded. Your transaction was aborted after {$maxRetryAttempts} attempts.",
            $previous->getCode(),
            $previous
        );
    }
}