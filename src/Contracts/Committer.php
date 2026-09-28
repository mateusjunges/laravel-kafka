<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

/**
 * Commits offsets when handlers call the commit methods of the consumer. In auto commit mode, offsets are
 * stored after each message is processed and committed by librdkafka in the background instead.
 */
interface Committer
{
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
