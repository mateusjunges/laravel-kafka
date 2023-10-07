<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Producers\MessageBatch;

final class MessageBatchPublished
{
    public function __construct(
        public readonly MessageBatch $batch,
        public readonly int $publishedCount,
    ) {
    }
}
