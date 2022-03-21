<?php

declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Illuminate\Support\Collection;

interface BatchConsumerInterface
{
    public function handle(Collection $collection): void;
}
