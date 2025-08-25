<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface MessageConsumer
{
    /**
     * Consume messages from a kafka topic in loop.
     *
     * @throws \RdKafka\Exception|\Carbon\Exceptions\Exception|\Junges\Kafka\Exceptions\ConsumerException
     */
    public function consume(): void;

    /** Requests the consumer to stop after it's finished processing any messages to allow graceful exit. */
    public function stopConsuming(): void;

    /** Will cancel the stopConsume request initiated by calling the stopConsume method */
    public function cancelStopConsume(): void;

    /** Count the number of messages consumed by this consumer */
    public function consumedMessagesCount(): int;

    /**
     * Commit offsets synchronously.
     *
     * @param  mixed  $messageOrOffsets  Can be:
     *                                   - null: Commit offsets for current assignment
     *                                   - \RdKafka\Message: Commit offset for a single topic+partition
     *                                   - ConsumerMessage: Commit offset for a single topic+partition
     *                                   - array of \RdKafka\TopicPartition: Commit offsets for provided partitions
     *
     * @throws \RdKafka\Exception
     */
    public function commit(mixed $messageOrOffsets = null): void;

    /**
     * Commit offsets asynchronously.
     *
     * @param  mixed  $message_or_offsets  Can be:
     *                                     - null: Commit offsets for current assignment
     *                                     - \RdKafka\Message: Commit offset for a single topic+partition
     *                                     - ConsumerMessage: Commit offset for a single topic+partition
     *                                     - array of \RdKafka\TopicPartition: Commit offsets for provided partitions
     *
     * @throws \RdKafka\Exception
     */
    public function commitAsync(mixed $message_or_offsets = null): void;

    /** Get the current partition assignment for this consumer */
    public function getAssignedPartitions(): array;
}
