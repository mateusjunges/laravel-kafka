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
     * Commit offsets synchronously. Without arguments, it commits the offsets of the current assignment.
     * Given a message, it commits the offset right after it, in its partition, and given an array of
     * topic partitions, it commits their offsets.
     *
     * @param  ConsumerMessage|Message|list<TopicPartition>|null  $messageOrOffsets
     */
    public function commit(ConsumerMessage|Message|array|null $messageOrOffsets = null): void;

    /**
     * Commit offsets asynchronously. It accepts the same arguments as commit().
     *
     * @param  ConsumerMessage|Message|list<TopicPartition>|null  $messageOrOffsets
     */
    public function commitAsync(ConsumerMessage|Message|array|null $messageOrOffsets = null): void;
}
