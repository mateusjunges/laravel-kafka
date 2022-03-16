<?php

declare(strict_types=1);

namespace Junges\Kafka\Support;

class Timer
{
    private float $startTime;
    private int $timeoutInMilliseconds;

    public function isTimedOut(): bool
    {
        return microtime(true) - $this->startTime >= $this->timeoutInMilliseconds / 1000;
    }

    public function start(int $timeoutInMilliseconds): void
    {
        $this->startTime = microtime(true);
        $this->timeoutInMilliseconds = $timeoutInMilliseconds;
    }
}
