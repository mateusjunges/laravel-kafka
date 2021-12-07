<?php

namespace Junges\Kafka\Contracts;

interface RetryStrategy
{
    public function getMaximumRetries(): int;

    /**
     * Returns the initial retry delay in seconds
     *
     * @return int
     */
    public function getInitialDelay(): int;

    public function useExponentialBackoff(): bool;
}
