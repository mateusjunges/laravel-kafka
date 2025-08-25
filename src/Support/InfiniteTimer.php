<?php declare(strict_types=1);

namespace Junges\Kafka\Support;

class InfiniteTimer extends Timer
{
    public function isTimedOut(): bool
    {
        return false;
    }
}
