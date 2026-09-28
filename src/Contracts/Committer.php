<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use RdKafka\Message;
use RdKafka\TopicPartition;

/**
 * Commits offsets when handlers call the commit methods of the consumer. In auto commit mode, offsets are
 * stored after each message is processed and committed by librdkafka in the background instead.
 */
interface Committer
{
    /**
     * Commit offsets synchronously.
     *
     * @param  ConsumerMessage|Message|list<TopicPartition>|null  $messageOrOffsets  What to commit:
     *                                                                               - null: the offsets of the current assignment.
     *                                                                               - A message: the offset right after the message, in its partition.
     *                                                                               - An array of topic partitions: the given offsets.
     */
    public function commit(ConsumerMessage|Message|array|null $messageOrOffsets = null): void;

    /**
     * Commit offsets asynchronously.
     *
     * @param  ConsumerMessage|Message|list<TopicPartition>|null  $messageOrOffsets  What to commit:
     *                                                                               - null: the offsets of the current assignment.
     *                                                                               - A message: the offset right after the message, in its partition.
     *                                                                               - An array of topic partitions: the given offsets.
     */
    public function commitAsync(ConsumerMessage|Message|array|null $messageOrOffsets = null): void;
}
