<?php declare(strict_types=1);

namespace Junges\Kafka\Commit\Contracts;

interface Sleeper
{
    /** Sleeps for the given time in microseconds. */
    public function sleep(int $timeInMicroseconds): void;
}
