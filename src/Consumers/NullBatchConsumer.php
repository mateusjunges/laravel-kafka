<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\CanConsumeBatchMessages;

class NullBatchConsumer implements CanConsumeBatchMessages
{
    /**
     * {@inheritdoc}
     */
    public function handle(Collection $collection): void
    {
    }
}
