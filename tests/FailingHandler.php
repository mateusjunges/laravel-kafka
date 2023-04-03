<?php

namespace Junges\Kafka\Tests;

use Exception;
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class FailingHandler
{
    /**
     * @var int
     */
    private $timesInvoked = 0;
    /**
     * @var int
     */
    private $timesToFail;
    /**
     * @var \Exception
     */
    private $exception;

    public function __construct(int $timesToFail, Exception $exception)
    {
        $this->timesToFail = $timesToFail;
        $this->exception = $exception;
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
