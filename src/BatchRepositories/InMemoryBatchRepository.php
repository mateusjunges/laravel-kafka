<?php

namespace Junges\Kafka\BatchRepositories;

use \RdKafka\Message;
use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\BatchRepository as BatchRepositoryContract;

class InMemoryBatchRepository implements BatchRepositoryContract
{
    private Collection $batch;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function push(Message $message): void
    {
        $this->batch->push($message);
    }

    /**
     * {@inheritdoc}
     */
    public function getBatch(): Collection
    {
        return $this->batch;
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchSize(): int
    {
        return $this->batch->count();
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        $this->batch = collect([]);
    }
}
