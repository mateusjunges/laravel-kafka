<?php

namespace Junges\Kafka;

use Carbon\Exceptions\Exception;
use Junges\Kafka\Commit\Contracts\Sleeper;

class Retryable
{
    private Sleeper $sleeper;
    private int $maximumRetries;
    private array $retryableErrors;

    public function __construct(Sleeper $sleeper, int $maximumRetries, array $retryableErrors)
    {
        $this->sleeper = $sleeper;
        $this->maximumRetries = $maximumRetries;
        $this->retryableErrors = $retryableErrors;
    }

    public function retry(
        callable $function,
        int $currentRetries = 0,
        int $delayInSeconds = 1,
        bool $exponentially = true
    ) {
        try {
            $function();
        } catch (Exception $exception) {
            if (in_array($exception->getCode(), $this->retryableErrors) && $currentRetries < $this->maximumRetries) {
                $this->sleeper->sleep((int)($delayInSeconds * 1e6));
                $this->retry(
                    $function,
                    ++$currentRetries,
                    $exponentially == true ? $delayInSeconds * 2 : $delayInSeconds
                );
                return;
            }

            throw $exception;
        }
    }
}
