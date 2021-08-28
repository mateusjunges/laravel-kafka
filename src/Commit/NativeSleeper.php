<?php

namespace Junges\Kafka\Commit;

use Junges\Kafka\Commit\Contracts\Sleeper;

class NativeSleeper implements Sleeper
{
    public function sleep(int $timeInMicroseconds): void
    {
        usleep($timeInMicroseconds);
    }
}
