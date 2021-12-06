<?php

namespace Junges\Kafka\Commit;

use Junges\Kafka\Commit\Contracts\Committer;
use RdKafka\Message;

class VoidCommitter implements Committer
{
    public function commitMessage(Message $message, bool $success): void
    {
    }

    public function commitDlq(Message $message): void
    {
    }
}
