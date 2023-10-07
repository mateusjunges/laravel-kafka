<?php declare(strict_types=1);

namespace Junges\Kafka\Config;

use Junges\Kafka\Consumers\CallableBatchConsumer;
use Junges\Kafka\Contracts\BatchMessageConsumer;
use Junges\Kafka\Contracts\BatchRepository as BatchRepositoryContract;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;
use Junges\Kafka\Support\Timer;

class BatchConfig implements HandlesBatchConfiguration
{
    public function __construct(
        private readonly CallableBatchConsumer $batchConsumer,
        private readonly Timer $timer,
        private readonly BatchRepositoryContract $batchRepository,
        private readonly bool $batchingEnabled = false,
        private readonly int $batchSizeLimit = 0,
        private readonly int $batchReleaseInterval = 0,
    ) {
    }

    public function isBatchingEnabled(): bool
    {
        return $this->batchingEnabled;
    }

    public function getBatchSizeLimit(): int
    {
        return $this->batchSizeLimit;
    }

    public function getBatchReleaseInterval(): int
    {
        return $this->batchReleaseInterval;
    }

    public function getConsumer(): BatchMessageConsumer
    {
        return $this->batchConsumer;
    }

    public function getTimer(): Timer
    {
        return $this->timer;
    }

    public function getBatchRepository(): BatchRepositoryContract
    {
        return $this->batchRepository;
    }
}
