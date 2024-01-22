<?php declare(strict_types=1);

namespace Junges\Kafka\BatchRepositories;

use \RdKafka\Message;
use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\BatchRepository as BatchRepositoryContract;

class NullBatchRepository implements BatchRepositoryContract
{
    public function push(Message $message): void
    {
    }

    public function getBatch(): Collection
    {
        return collect();
    }

    public function getBatchSize(): int
    {
        return 0;
    }

    public function reset(): void
    {
    }
}
