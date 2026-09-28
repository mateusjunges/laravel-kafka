<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;
use RdKafka\TopicPartition;

/**
 * Dispatched when partitions are assigned to a consumer, on a consumer group rebalance. With cooperative
 * rebalancing, the partitions are added to the ones the consumer already had.
 */
final readonly class PartitionsAssigned
{
    /** @param list<TopicPartition> $partitions */
    public function __construct(
        public Consumer $consumer,
        public array $partitions,
    ) {}
}
