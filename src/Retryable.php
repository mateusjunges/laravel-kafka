<?php

namespace Junges\Kafka;

use Exception;
use Junges\Kafka\Commit\Contracts\Sleeper;

class Retryable
{
    /**
     * @var \Junges\Kafka\Commit\Contracts\Sleeper
     */
    private $sleeper;
    /**
     * @var int
     */
    private $maximumRetries;
    /**
     * @var mixed[]|null
     */
    private $retryableErrors;
    public function __construct(Sleeper $sleeper, int $maximumRetries, ?array $retryableErrors)
    {
        $this->sleeper = $sleeper;
        $this->maximumRetries = $maximumRetries;
        $this->retryableErrors = $retryableErrors;
    }

    /**
     * @param callable $function
     * @param int $currentRetries
     * @param int $delayInSeconds
     * @param bool $exponentially
     * @throws \Exception
     */
    public function retry(
        callable $function,
        int $currentRetries = 0,
        int $delayInSeconds = 1,
        bool $exponentially = true
    ): void {
        try {
            $function();
        } catch (Exception $exception) {
            if (
                $currentRetries < $this->maximumRetries
                && (is_null($this->retryableErrors) || in_array($exception->getCode(), $this->retryableErrors))
            ) {
                $this->sleeper->sleep((int)($delayInSeconds * 1e6));
                $this->retry(
                    $function,
                    ++$currentRetries,
                    $exponentially === true ? $delayInSeconds * 2 : $delayInSeconds
                );

                return;
            }

            throw $exception;
        }
    }
}
