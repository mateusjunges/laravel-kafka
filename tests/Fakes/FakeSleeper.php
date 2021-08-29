<?php

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Commit\Contracts\Sleeper;

class FakeSleeper implements Sleeper
{
    private array $sleeps = [];

    public function sleep(int $timeInMicroseconds): void
    {
        $this->sleeps[] = $timeInMicroseconds;
    }

    public function getSleeps(): array
    {
        return $this->sleeps;
    }
}
