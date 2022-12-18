<?php declare(strict_types=1);

namespace Junges\Kafka\Handlers\RetryStrategies;

use Junges\Kafka\Contracts\RetryStrategy;

class DefaultRetryStrategy implements RetryStrategy
{
    public function getMaximumRetries(): int
    {
        return 6;
    }

    public function getInitialDelay(): int
    {
        return 1;
    }

    public function useExponentialBackoff(): bool
    {
        return true;
    }
}
