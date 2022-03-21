<?php

namespace Junges\Kafka\Contracts;

use Illuminate\Support\Collection;

interface BatchConsumerInterface
{
    public function handle(Collection $collection): void;
}
