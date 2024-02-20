<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Closure;
use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\BatchMessageConsumer;
use Junges\Kafka\Contracts\MessageConsumer;

readonly class CallableBatchConsumer implements BatchMessageConsumer
{
    public function __construct(private Closure $batchHandler)
    {
    }

    public function handle(Collection $collection, MessageConsumer $consumer): void
    {
        ($this->batchHandler)($collection, $consumer);
    }
}
