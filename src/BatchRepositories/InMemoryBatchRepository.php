<?php declare(strict_types=1);

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
