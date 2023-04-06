<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use RdKafka\Message;

interface Committer
{
    /** Commits the given message.  */
    public function commitMessage(Message $message, bool $success): void;

    /** Commits the given message to the Dead Letter Queue. */
    public function commitDlq(Message $message): void;
}
