<?php

namespace Junges\Kafka\Commit;

use Junges\Kafka\Commit\Contracts\Committer;
use Junges\Kafka\MessageCounter;

class BatchCommitter implements Committer
{
    private int $commits = 0;

    public function __construct(
        private Committer $committer,
        private MessageCounter $messageCounter,
        private int $batchSize
    ) {
    }

    public function commitMessage(): void
    {
        $this->commits++;
        if ($this->maxMessagesLimitReached() || $this->commits >= $this->batchSize) {
            $this->committer->commitMessage();
            $this->commits = 0;
        }
    }

    private function maxMessagesLimitReached(): bool
    {
        return $this->messageCounter->maxMessagesLimitReached();
    }

    public function commitDlq(): void
    {
        $this->committer->commitDlq();
        $this->commits = 0;
    }
}
