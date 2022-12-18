<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Closure;
use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\BatchMessageConsumer;

class CallableBatchConsumer implements BatchMessageConsumer
{
    public function __construct(private readonly Closure $batchHandler)
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
