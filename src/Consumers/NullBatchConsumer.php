<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\BatchMessageConsumer;
use Junges\Kafka\Contracts\MessageConsumer;

readonly class NullBatchConsumer implements BatchMessageConsumer
{
    public function handle(Collection $collection, MessageConsumer $consumer): void
    {
    }
}
