<?php

namespace Junges\Kafka\Consumers;

use Closure;
use Illuminate\Support\Collection;

class CallableBatchConsumer implements BatchConsumerInterface
{
    public function __construct(private Closure $batchHandler)
    {
    }

    /**
     * Handle received batch of messages
     *
     * @param Collection $collection
     * @return void
     */
    public function handle(Collection $collection): void
    {
        ($this->batchHandler)($collection);
    }
}
