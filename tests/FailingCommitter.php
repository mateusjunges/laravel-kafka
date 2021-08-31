<?php

namespace Junges\Kafka\Tests;

use Exception;
use Junges\Kafka\Commit\Contracts\Committer;

class FailingCommitter implements Committer
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
     * @throws \Exception
     */
    public function commitMessage(): void
    {
        $this->timesTriedToCommitMessage++;
        $this->commit();
    }

    /**
     * @throws \Exception
     */
    public function commitDlq(): void
    {
        $this->timesTriedToCommitDlq++;
        $this->commit();
    }

    /**
     * @throws \Exception
     */
    private function commit(): void
    {
        $this->commitCount++;
        if ($this->commitCount > $this->timesToFail) {
            $this->commitCount = 0;

            return;
        }

        throw $this->failure;
    }

    public function getTimesTriedToCommitMessage(): int
    {
        return $this->timesTriedToCommitMessage;
    }

    public function getTimesTriedToCommitDlq(): int
    {
        return $this->timesTriedToCommitDlq;
    }
}
