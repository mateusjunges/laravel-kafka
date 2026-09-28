<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\Consumer;

/**
 * Dispatched when librdkafka reports the statistics of a consumer or a producer, every "statistics.interval.ms",
 * which is disabled by default. Consumers report them while consuming, and producers while publishing or
 * flushing. See https://github.com/confluentinc/librdkafka/blob/master/STATISTICS.md for their content.
 */
final readonly class StatisticsReported
{
    /**
     * @param  array<string, mixed>  $statistics
     * @param  Consumer|null  $consumer  The consumer that reported them, or null when they were reported by a producer.
     */
    public function __construct(
        public array $statistics,
        public string $connection,
        public ?Consumer $consumer = null,
    ) {}
}
