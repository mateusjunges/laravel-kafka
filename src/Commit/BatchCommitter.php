<?php

namespace Junges\Kafka\Commit;

use Junges\Kafka\Commit\Contracts\Committer;
use Junges\Kafka\MessageCounter;
use RdKafka\Message;

class BatchCommitter implements Committer
{
    private int $commits = 0;

    public function __construct(
        private Committer $committer,
        private MessageCounter $messageCounter,
        private int $batchSize
    ) {
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
