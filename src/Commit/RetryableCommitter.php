<?php

namespace Junges\Kafka\Commit;

use Junges\Kafka\Commit\Contracts\Committer;
use Junges\Kafka\Commit\Contracts\Sleeper;
use Junges\Kafka\Retryable;

class RetryableCommitter implements Committer
{
    private const RETRYABLE_ERRORS = [
        RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT
    ];

    private Retryable $retryable;

    public function __construct(
        private Committer $committer,
        Sleeper $sleeper,
        int $maximumRetries = 6)
    {
        $this->retryable = new Retryable($sleeper, $maximumRetries, self::RETRYABLE_ERRORS);
    }

    public function commitMessage(): void
    {
        $this->retryable->retry(function () {
            $this->committer->commitMessage();
        });
    }

    public function commitDlq(): void
    {
        $this->retryable->retry(function () {
            $this->committer->commitDlq();
        });
    }
}
