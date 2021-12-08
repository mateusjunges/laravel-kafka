<?php

namespace Junges\Kafka\Contracts;

interface RetryStrategy
{
    /**
     * The maximum number of retries
     *
     * @return int
     */
    public function getMaximumRetries(): int;

    /**
     * The initial retry delay in seconds
     *
     * @return int
     */
    public function getInitialDelay(): int;

    /**
     * Whether to double the initial delay between retries.
     *
     * @return bool
     */
    public function useExponentialBackoff(): bool;
}
