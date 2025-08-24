<?php declare(strict_types=1);

namespace Junges\Kafka\Config;

enum RebalanceStrategy: string
{
    /**
     * The range assignor works on a per-topic basis. For each topic, it lays out the available partitions in numeric order
     * and the consumer threads in lexicographic order. It then divides the number of partitions by the total number of
     * consumer streams (threads) to determine the number of partitions to assign to each consumer.
     */
    case RANGE = 'range';

    /**
     * The round-robin assignor lays out all the available partitions and all the available consumer threads.
     * It then proceeds to do a round-robin assignment from partition to consumer thread.
     */
    case ROUND_ROBIN = 'roundrobin';

    /**
     * The sticky assignor serves two purposes. First, it guarantees an assignment that is as balanced as possible.
     * Second, it preserves as many existing assignment as possible when a reassignment occurs.
     */
    case STICKY = 'sticky';

    /**
     * The cooperative sticky assignor is identical to the sticky assignor but allows for cooperative rebalancing.
     */
    case COOPERATIVE_STICKY = 'cooperative-sticky';

    /** @return array<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
