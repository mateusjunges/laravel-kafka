<?php declare(strict_types=1);

namespace Junges\Kafka\Support;

class Timer
{
    /** Shows when a timer is running */
    private float $startTime;

    /** Shows after what period of time in milliseconds timer is considered as timed out */
    private int $timeoutInMilliseconds;

    /** Determines if the timer has timed out */
    public function isTimedOut(): bool
    {
        return microtime(true) - $this->startTime >= $this->timeoutInMilliseconds / 1000;
    }

    /** Starts a timer, Captures a start time and Captures a timeout in milliseconds */
    public function start(int $timeoutInMilliseconds): void
    {
        $this->startTime = microtime(true);
        $this->timeoutInMilliseconds = $timeoutInMilliseconds;
    }
}
