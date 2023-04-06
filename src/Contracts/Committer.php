<?php

namespace Junges\Kafka\Contracts;

use RdKafka\Message;

interface Committer
{
    /**
     * Commits the given message.
     *
     * @param  \RdKafka\Message  $message
     * @param  bool  $success
     * @return void
     */
    public function commitMessage(Message $message, bool $success): void;

    /**
     * Commits the given message to the Dead Letter Queue.
     *
     * @param  \RdKafka\Message  $message
     * @return void
     */
    public function commitDlq(Message $message): void;
}
