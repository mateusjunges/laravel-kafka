<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use RdKafka\Message;
use RdKafka\TopicPartition;
use Throwable;

interface Logger
{
    /** Log an error that happened while consuming the given message. */
    public function error(Message $message, ?Throwable $e = null, string $prefix = 'ERROR'): void;

    /**
     * Log an error that happened while committing offsets. It receives the argument given to the commit
     * methods of the consumer: a message, a list of topic partitions, or null when the offsets of the
     * current assignment were being committed.
     *
     * @param  ConsumerMessage|Message|list<TopicPartition>|null  $messageOrOffsets
     */
    public function commitError(ConsumerMessage|Message|array|null $messageOrOffsets, Throwable $e): void;
}
