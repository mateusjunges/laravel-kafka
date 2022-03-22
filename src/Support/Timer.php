<?php

namespace Junges\Kafka\Support;

class Timer
{
    /**
     * Shows when a timer is running
     *
     * @var float
     */
    private float $startTime;

    /**
     * Shows after what period of time in milliseconds timer is considered as timed out
     *
     * @var int
     */
    private int $timeoutInMilliseconds;

    /**
     * Determines if the timer has timed out
     *
     * @return bool
     */
    public function isTimedOut(): bool
    {
        return microtime(true) - $this->startTime >= $this->timeoutInMilliseconds / 1000;
    }

    /**
     * Starts a timer
     * Captures a start time
     * Captures a timeout in milliseconds
     *
     * @param int $timeoutInMilliseconds
     * @return void
     */
    public function start(int $timeoutInMilliseconds): void
    {
        $this->startTime = microtime(true);
        $this->timeoutInMilliseconds = $timeoutInMilliseconds;
    }
}
