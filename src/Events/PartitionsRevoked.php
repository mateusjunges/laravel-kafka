<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;
use RdKafka\TopicPartition;

/**
 * Dispatched when partitions are revoked from a consumer, on a consumer group rebalance. With cooperative
 * rebalancing, the consumer keeps the partitions that were not revoked.
 */
final readonly class PartitionsRevoked
{
    /** @param list<TopicPartition> $partitions */
    public function __construct(
        public Consumer $consumer,
        public array $partitions,
    ) {}
}
