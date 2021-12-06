<?php

namespace Junges\Kafka\Commit\Contracts;

use RdKafka\Message;

interface Committer
{
    public function commitMessage(Message $message, bool $success): void;

    public function commitDlq(Message $message): void;
}
