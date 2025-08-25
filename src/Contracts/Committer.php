<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use RdKafka\Message;

interface Committer
{
    /** Commits the given message.  */
    public function commitMessage(Message $message, bool $success): void;

    /** Commits the given message to the Dead Letter Queue. */
    public function commitDlq(Message $message): void;

    /**
     * Commit offsets synchronously.
     *
     * @param  mixed  $messageOrOffsets  Can be:
     *                                   - null: Commit offsets for current assignment
     *                                   - \RdKafka\Message: Commit offset for a single topic+partition
     *                                   - \Junges\Kafka\Contracts\ConsumerMessage: Commit offset for a single topic+partition
     *                                   - array of \RdKafka\TopicPartition: Commit offsets for provided partitions
     */
    public function commit(mixed $messageOrOffsets = null): void;

    /**
     * Commit offsets asynchronously.
     *
     * @param  mixed  $messageOrOffsets  Can be:
     *                                   - null: Commit offsets for current assignment
     *                                   - \RdKafka\Message: Commit offset for a single topic+partition
     *                                   - \Junges\Kafka\Contracts\ConsumerMessage: Commit offset for a single topic+partition
     *                                   - array of \RdKafka\TopicPartition: Commit offsets for provided partitions
     */
    public function commitAsync(mixed $messageOrOffsets = null): void;
}
