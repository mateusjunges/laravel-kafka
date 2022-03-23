<?php

namespace Junges\Kafka\Consumers;

use Closure;
use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\CanConsumeBatchMessages;

class CallableBatchConsumer implements CanConsumeBatchMessages
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
