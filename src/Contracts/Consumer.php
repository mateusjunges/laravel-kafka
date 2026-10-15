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
     * @throws \RdKafka\Exception|\Junges\Kafka\Exceptions\ConsumerException
     */
    public function consume(): void;

    /** Requests the consumer to stop after it's finished processing any messages to allow graceful exit. */
    public function stopConsuming(): void;

    /** Will cancel the stopConsume request initiated by calling the stopConsume method */
    public function cancelStopConsume(): void;

    /** Count the number of messages consumed by this consumer */
    public function consumedMessagesCount(): int;

    /**
     * Commit offsets synchronously. Without arguments, it commits the offsets of the current assignment.
     * Given a message, it commits the offset right after it, in its partition, and given an array of
     * topic partitions, it commits their offsets.
     *
     * @param  ConsumerMessage|Message|list<TopicPartition>|null  $messageOrOffsets
     *
     * @throws \RdKafka\Exception
     */
    public function commit(ConsumerMessage|Message|array|null $messageOrOffsets = null): void;

    /**
     * Commit offsets asynchronously. It accepts the same arguments as commit().
     *
     * @param  ConsumerMessage|Message|list<TopicPartition>|null  $messageOrOffsets
     *
     * @throws \RdKafka\Exception
     */
    public function commitAsync(ConsumerMessage|Message|array|null $messageOrOffsets = null): void;

    /** Get the current partition assignment for this consumer */
    public function getAssignedPartitions(): array;

    /**
     * Stop fetching messages from the given partitions, or from every assigned partition, until they are resumed.
     * Partitions are resumed when they are revoked, and a partition assigned again on a rebalance is not paused.
     *
     * @param  list<TopicPartition>|null  $partitions
     *
     * @throws \RdKafka\Exception
     */
    public function pause(?array $partitions = null): void;

    /**
     * Resume fetching messages from the given paused partitions, or from every assigned partition.
     *
     * @param  list<TopicPartition>|null  $partitions
     *
     * @throws \RdKafka\Exception
     */
    public function resume(?array $partitions = null): void;

    /** Get the name of this consumer, which identifies it in events and when restarting it. */
    public function getName(): string;

    /** Get the name of the connection this consumer consumes from. */
    public function getConnectionName(): string;

    /** Get the consumer group of this consumer. */
    public function getGroupId(): ?string;

    /**
     * Get the topics this consumer subscribes to.
     *
     * @return list<string>
     */
    public function getTopics(): array;
}
