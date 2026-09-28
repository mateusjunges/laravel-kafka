<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

/** How far behind a consumer group is on a partition. */
final readonly class PartitionLag
{
    /**
     * @param  int|null  $committedOffset  The offset of the next message the group consumes, or null when the group never committed an offset for the partition.
     * @param  int  $lowWatermark  The offset of the first message available in the partition.
     * @param  int  $highWatermark  The offset the next message published to the partition gets.
     * @param  int|null  $lag  How many messages the group has not consumed yet, or null when it never committed an offset for the partition.
     */
    public function __construct(
        public string $topic,
        public int $partition,
        public ?int $committedOffset,
        public int $lowWatermark,
        public int $highWatermark,
        public ?int $lag,
    ) {}
}
