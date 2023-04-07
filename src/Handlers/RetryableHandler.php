<?php declare(strict_types=1);

namespace Junges\Kafka\Handlers;

use Closure;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\RetryStrategy;
use Junges\Kafka\Contracts\Sleeper;
use Junges\Kafka\Retryable;

class RetryableHandler
{
    public function __construct(private readonly Closure $handler, private readonly RetryStrategy $retryStrategy, private readonly Sleeper $sleeper)
    {
    }

    public function __invoke(ConsumerMessage $message): void
    {
        $retryable = new Retryable($this->sleeper, $this->retryStrategy->getMaximumRetries(), null);
        $retryable->retry(
            fn () => ($this->handler)($message),
            0,
            $this->retryStrategy->getInitialDelay(),
            $this->retryStrategy->useExponentialBackoff()
        );
    }
}
