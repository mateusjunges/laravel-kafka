<?php

namespace Junges\Kafka\Tests;

use Exception;
use Junges\Kafka\Commit\Contracts\Committer;
use RdKafka\Message;

class FailingCommitter implements Committer
{
    /**
     * @var int
     */
    private $timesToFail;
    /**
     * @var \Exception
     */
    private $failure;
    /**
     * @var int
     */
    private $timesTriedToCommitMessage = 0;
    /**
     * @var int
     */
    private $timesTriedToCommitDlq = 0;
    /**
     * @var int
     */
    private $commitCount = 0;

    public function __construct(Exception $failure, int $timesToFail)
    {
        $this->failure = $failure;
        $this->timesToFail = $timesToFail;
    }

    /**
     * @throws \Exception
     */
    public function commitMessage(Message $message = null, bool $success = null): void
    {
        $this->timesTriedToCommitMessage++;
        $this->commit();
    }

    /**
     * @throws \Exception
     */
    public function commitDlq(Message $message): void
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
