<?php

namespace Junges\Kafka\Commit\Contracts;

interface Sleeper
{
    /**
     * Sleeps for the given time in microseconds.
     *
     * @param  int  $timeInMicroseconds
     * @return void
     */
    public function sleep(int $timeInMicroseconds): void;
}
