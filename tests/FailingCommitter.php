<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Exception;
use Junges\Kafka\Contracts\Committer;
use RdKafka\Message;

final class FailingCommitter implements Committer
{
    private int $timesToFail;

    private Exception $failure;

    private int $timesTriedToCommitMessage = 0;

    private int $timesTriedToCommitDlq = 0;

    private int $commitCount = 0;

    public function __construct(Exception $failure, int $timesToFail)
    {
        $this->failure = $failure;
        $this->timesToFail = $timesToFail;
    }

    /**
     * @throws Exception
     */
    public function commitMessage(?Message $message = null, ?bool $success = null): void
    {
        $this->timesTriedToCommitMessage++;
        $this->doCommit();
    }

    /**
     * @throws Exception
     */
    public function commitDlq(Message $message): void
    {
        $this->timesTriedToCommitDlq++;
        $this->doCommit();
    }

    public function getTimesTriedToCommitMessage(): int
    {
        return $this->timesTriedToCommitMessage;
    }

    public function getTimesTriedToCommitDlq(): int
    {
        return $this->timesTriedToCommitDlq;
    }

    /**
     * @throws Exception
     */
    public function commit(mixed $messageOrOffsets = null): void
    {
        $this->doCommit();
    }

    /**
     * @throws Exception
     */
    public function commitAsync(mixed $messageOrOffsets = null): void
    {
        $this->doCommit();
    }

    /**
     * @throws Exception
     */
    private function doCommit(): void
    {
        $this->commitCount++;

        if ($this->commitCount > $this->timesToFail) {
            $this->commitCount = 0;

            return;
        }

        throw $this->failure;
    }
}
