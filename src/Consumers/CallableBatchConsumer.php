<?php

namespace Junges\Kafka\Consumers;

use Closure;
use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\BatchConsumerInterface;

class CallableBatchConsumer implements BatchConsumerInterface
{
    public function __construct(private Closure $batchHandler)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Collection $collection): void
    {
        ($this->batchHandler)($collection);
    }
}
