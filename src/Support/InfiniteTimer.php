<?php declare(strict_types=1);

namespace Junges\Kafka\Support;

use Override;

class InfiniteTimer extends Timer
{
    #[Override]
    public function isTimedOut(): bool
    {
        return false;
    }
}
