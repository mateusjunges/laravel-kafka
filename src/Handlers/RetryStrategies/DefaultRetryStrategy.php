<?php

namespace Junges\Kafka\Handlers\RetryStrategies;

use Junges\Kafka\Contracts\RetryStrategy;

class DefaultRetryStrategy implements RetryStrategy
{
    /**
     * The maximum number of retries
     *
     * @return int
     */
    public function getMaximumRetries(): int
    {
        return 6;
    }

    /**
     * The initial retry delay in seconds
     *
     * @return int
     */
    public function getInitialDelay(): int
    {
        return 1;
    }

    /**
     * Whether to double the initial delay between retries.
     *
     * @return bool
     */
    public function useExponentialBackoff(): bool
    {
        return true;
    }
}
