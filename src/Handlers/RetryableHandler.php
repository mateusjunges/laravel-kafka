<?php

namespace Junges\Kafka\Handlers;

use Closure;
use Junges\Kafka\Commit\Contracts\Sleeper;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Contracts\RetryStrategy;
use Junges\Kafka\Retryable;

class RetryableHandler
{
    /**
     * @var \Closure
     */
    private $handler;
    /**
     * @var \Junges\Kafka\Contracts\RetryStrategy
     */
    private $retryStrategy;
    /**
     * @var \Junges\Kafka\Commit\Contracts\Sleeper
     */
    private $sleeper;
    public function __construct(Closure $handler, RetryStrategy $retryStrategy, Sleeper $sleeper)
    {
        $this->handler = $handler;
        $this->retryStrategy = $retryStrategy;
        $this->sleeper = $sleeper;
    }

    public function __invoke(KafkaConsumerMessage $message): void
    {
        $retryable = new Retryable($this->sleeper, $this->retryStrategy->getMaximumRetries(), null);
        $retryable->retry(
            function () use ($message) {
                return ($this->handler)($message);
            },
            0,
            $this->retryStrategy->getInitialDelay(),
            $this->retryStrategy->useExponentialBackoff()
        );
    }
}
