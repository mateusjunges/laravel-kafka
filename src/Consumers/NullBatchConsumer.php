<?php

namespace Junges\Kafka\Consumers;

use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\BatchConsumerInterface;

final class NullBatchConsumer implements BatchConsumerInterface
{
    public function handle(Collection $collection): void
    {
    }
}
