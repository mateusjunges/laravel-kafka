<?php

declare(strict_types=1);

namespace Junges\Kafka\Config;

use Junges\Kafka\BatchRepositories\BatchRepositoryInterface;
use Junges\Kafka\Consumers\BatchConsumerInterface;
use Junges\Kafka\Consumers\CallableBatchConsumer;
use Junges\Kafka\Support\Timer;

class BatchConfig implements BatchConfigInterface
{
    public function __construct(
        private CallableBatchConsumer $batchConsumer,
        private Timer $timer,
        private BatchRepositoryInterface $batchRepository,
        private bool $batchingEnabled = false,
        private int $batchSizeLimit = 0,
        private int $batchReleaseIntervalInMilliseconds = 0,
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

    public function getBatchReleaseIntervalInMilliseconds(): int
    {
        return $this->batchReleaseIntervalInMilliseconds;
    }

    public function getConsumer(): BatchConsumerInterface
    {
        return $this->batchConsumer;
    }

    public function getTimer(): Timer
    {
        return $this->timer;
    }

    public function getBatchRepository(): BatchRepositoryInterface
    {
        return $this->batchRepository;
    }
}
