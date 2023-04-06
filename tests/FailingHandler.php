<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Exception;
use Junges\Kafka\Contracts\ConsumerMessage;

final class FailingHandler
{
    private int $timesInvoked = 0;

    public function __construct(private readonly int $timesToFail, private readonly Exception $exception)
    {
    }

    public function __invoke(ConsumerMessage $message): void
    {
        if ($this->timesInvoked++ < $this->timesToFail) {
            throw $this->exception;
        }
    }

    public function getTimesInvoked(): int
    {
        return $this->timesInvoked;
    }
}
