<?php

namespace Junges\Kafka\Contracts;

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
