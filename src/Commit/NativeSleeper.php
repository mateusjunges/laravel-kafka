<?php declare(strict_types=1);

namespace Junges\Kafka\Commit;

use Junges\Kafka\Contracts\Sleeper;

class NativeSleeper implements Sleeper
{
    public function sleep(int $timeInMicroseconds): void
    {
        usleep($timeInMicroseconds);
    }
}
