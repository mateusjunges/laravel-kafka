<?php

namespace Junges\Kafka\Commit;

use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Commit\Contracts\Committer;
use Junges\Kafka\Commit\Contracts\Sleeper;
use Junges\Kafka\Retryable;
use RdKafka\Message;

class RetryableCommitter implements Committer
{
    private const RETRYABLE_ERRORS = [
        RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT,
    ];

    private Committer $committer;
    private Retryable $retryable;

    #[Pure]
    public function __construct(Committer $committer, Sleeper $sleeper, int $maximumRetries = 6)
    {
        $this->committer = $committer;
        $this->retryable = new Retryable($sleeper, $maximumRetries, self::RETRYABLE_ERRORS);
    }

    public function commitMessage(Message $message, bool $success): void
    {
        $this->retryable->retry(fn () => $this->committer->commitMessage($message, $success));
    }

    public function commitDlq(Message $message): void
    {
        $this->retryable->retry(fn () => $this->committer->commitDlq($message));
    }
}
