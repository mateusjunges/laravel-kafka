<?php declare(strict_types=1);

namespace Junges\Kafka\Commit;

use Junges\Kafka\Contracts\Committer;
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
