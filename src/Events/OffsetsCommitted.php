<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;
use RdKafka\TopicPartition;

/**
 * Dispatched when a consumer committed offsets, either in the background with auto commit, or through
 * commit() and commitAsync(). The offset of each partition is the offset of the next message to consume.
 */
final readonly class OffsetsCommitted
{
    /** @param list<TopicPartition> $partitions */
    public function __construct(
        public Consumer $consumer,
        public array $partitions,
    ) {}
}
