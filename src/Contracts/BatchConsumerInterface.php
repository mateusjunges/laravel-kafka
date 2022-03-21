<?php

namespace Junges\Kafka\Consumers;

use Illuminate\Support\Collection;

interface BatchConsumerInterface
{
    public function handle(Collection $collection): void;
}
