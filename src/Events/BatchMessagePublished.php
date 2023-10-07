<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Contracts\ProducerMessage;

final class BatchMessagePublished
{
    public function __construct(
        public readonly ProducerMessage $message,
        public readonly string $batchUuid,
    ) {
    }
}
