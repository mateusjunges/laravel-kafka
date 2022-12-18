<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\BatchMessageConsumer;

class NullBatchConsumer implements BatchMessageConsumer
{
    /**
     * {@inheritdoc}
     */
    public function handle(Collection $collection): void
    {
    }
}
