<?php

namespace Junges\Kafka\Consumers;

use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\CanConsumeBatchMessages;

class NullBatchConsumer implements CanConsumeBatchMessages
{
    /**
     * {@inheritdoc}
     */
    public function handle(Collection $collection): void
    {
    }
}
