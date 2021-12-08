<?php

namespace Junges\Kafka\Tests;

use Exception;
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class FailingHandler
{
    private int $timesInvoked = 0;

    public function __construct(private int $timesToFail, private Exception $exception)
    {
    }

    public function __invoke(KafkaConsumerMessage $message): void
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
