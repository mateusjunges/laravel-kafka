<?php

declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Illuminate\Support\Collection;

final class NullBatchConsumer implements BatchConsumerInterface
{
    public function handle(Collection $collection): void
    {
    }
}
