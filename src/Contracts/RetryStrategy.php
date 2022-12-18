<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface RetryStrategy
{
    /** The maximum number of retries */
    public function getMaximumRetries(): int;

    /** The initial retry delay in seconds */
    public function getInitialDelay(): int;

    /** Whether to double the initial delay between retries. */
    public function useExponentialBackoff(): bool;
}
