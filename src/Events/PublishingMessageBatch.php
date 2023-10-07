<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Junges\Kafka\Producers\MessageBatch;

final class PublishingMessageBatch
{
    public function __construct(
        public readonly MessageBatch $batch,
    ) {
    }
}
