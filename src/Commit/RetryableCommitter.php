<?php declare(strict_types=1);

namespace Junges\Kafka\Commit;

use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Contracts\Committer;
use Junges\Kafka\Contracts\Sleeper;
use Junges\Kafka\Retryable;
use RdKafka\Message;

class RetryableCommitter implements Committer
{
    private const RETRYABLE_ERRORS = [
        RD_KAFKA_RESP_ERR_ILLEGAL_GENERATION,
        RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT,
        RD_KAFKA_RESP_ERR_REBALANCE_IN_PROGRESS,
    ];

    private readonly Retryable $retryable;

    #[Pure]
    public function __construct(private readonly Committer $committer, Sleeper $sleeper, int $maximumRetries = 6)
    {
        $this->retryable = new Retryable($sleeper, $maximumRetries, self::RETRYABLE_ERRORS);
    }

    /** @throws \Carbon\Exceptions\Exception */
    public function commitMessage(Message $message, bool $success): void
    {
        $this->retryable->retry(fn () => $this->committer->commitMessage($message, $success));
    }

    /** @throws \Carbon\Exceptions\Exception */
    public function commitDlq(Message $message): void
    {
        $this->retryable->retry(fn () => $this->committer->commitDlq($message));
    }

    /** @throws \Carbon\Exceptions\Exception */
    public function commit(mixed $messageOrOffsets = null): void
    {
        $this->retryable->retry(fn () => $this->committer->commit($messageOrOffsets));
    }

    /** @throws \Carbon\Exceptions\Exception */
    public function commitAsync(mixed $messageOrOffsets = null): void
    {
        $this->retryable->retry(fn () => $this->committer->commitAsync($messageOrOffsets));
    }
}
