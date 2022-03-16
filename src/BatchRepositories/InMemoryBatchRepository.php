<?php

declare(strict_types=1);

namespace Junges\Kafka\BatchRepositories;

use \RdKafka\Message;
use Illuminate\Support\Collection;

final class InMemoryBatchRepository implements BatchRepositoryInterface
{
    private Collection $batch;

    public function __construct()
    {
        $this->reset();
    }

    public function push(Message $message): void
    {
        $this->batch->push($message);
    }

    public function getBatch(): Collection
    {
        return $this->batch;
    }

    public function getBatchSize(): int
    {
        return $this->batch->count();
    }

    public function reset(): void
    {
        $this->batch = collect([]);
    }
}
