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
