<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use RdKafka\Message;
use RdKafka\TopicPartition;

/** The consumer that handles messages, passed to handlers and middlewares along with each message. */
interface Consumer
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
     * @param  ConsumerMessage|Message|list<TopicPartition>|null  $messageOrOffsets  What to commit:
     *                                                                               - null: the offsets of the current assignment.
     *                                                                               - A message: the offset right after the message, in its partition.
     *                                                                               - An array of topic partitions: the given offsets.
     *
     * @throws \RdKafka\Exception
     */
    public function commit(ConsumerMessage|Message|array|null $messageOrOffsets = null): void;

    /**
     * Commit offsets asynchronously.
     *
     * @param  ConsumerMessage|Message|list<TopicPartition>|null  $messageOrOffsets  What to commit:
     *                                                                               - null: the offsets of the current assignment.
     *                                                                               - A message: the offset right after the message, in its partition.
     *                                                                               - An array of topic partitions: the given offsets.
     *
     * @throws \RdKafka\Exception
     */
    public function commitAsync(ConsumerMessage|Message|array|null $messageOrOffsets = null): void;

    /** Get the current partition assignment for this consumer */
    public function getAssignedPartitions(): array;
}
