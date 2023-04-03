<?php

namespace Junges\Kafka\Commit;

use Junges\Kafka\Commit\Contracts\Committer;
use Junges\Kafka\MessageCounter;
use RdKafka\Message;

class BatchCommitter implements Committer
{
    /**
     * @var int
     */
    private $commits = 0;
    /**
     * @var \Junges\Kafka\Commit\Contracts\Committer
     */
    private $committer;
    /**
     * @var \Junges\Kafka\MessageCounter
     */
    private $messageCounter;
    /**
     * @var int
     */
    private $batchSize;

    public function __construct(Committer $committer, MessageCounter $messageCounter, int $batchSize)
    {
        $this->committer = $committer;
        $this->messageCounter = $messageCounter;
        $this->batchSize = $batchSize;
    }

    public function commitMessage(Message $message, bool $success): void
    {
        $this->commits++;
        if ($this->maxMessagesLimitReached() || $this->commits >= $this->batchSize) {
            $this->committer->commitMessage($message, $success);
            $this->commits = 0;
        }
    }

    private function maxMessagesLimitReached(): bool
    {
        return $this->messageCounter->maxMessagesLimitReached();
    }

    public function commitDlq(Message $message): void
    {
        $this->committer->commitDlq($message);
        $this->commits = 0;
    }
}
