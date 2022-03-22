<?php

namespace Junges\Kafka\Consumers;

use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\BatchConsumerInterface;

class NullBatchConsumer implements BatchConsumerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(Collection $collection): void
    {
    }
}
