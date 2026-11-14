<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;
use RdKafka\TopicPartition;

/** Dispatched when a consumer could not commit offsets, either in the background with auto commit, or through commit() and commitAsync(). */
final readonly class OffsetCommitFailed
{
    /** @param list<TopicPartition> $partitions */
    public function __construct(
        public Consumer $consumer,
        public array $partitions,
        public int $errorCode,
        public string $error,
    ) {}
}
