<?php

namespace Junges\Kafka\BatchRepositories;

use \RdKafka\Message;
use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\BatchRepository as BatchRepositoryContract;

class NullBatchRepository implements BatchRepositoryContract
{
    /**
     * {@inheritdoc}
     */
    public function push(Message $message): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBatch(): Collection
    {
        return collect([]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchSize(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
    }
}
