<?php

namespace Junges\Kafka\BatchRepositories;

use \RdKafka\Message;
use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\BatchRepositoryInterface;

class NullBatchRepository implements BatchRepositoryInterface
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
