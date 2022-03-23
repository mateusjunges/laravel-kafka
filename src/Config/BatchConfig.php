<?php

namespace Junges\Kafka\Config;

use Junges\Kafka\Consumers\CallableBatchConsumer;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;
use Junges\Kafka\Contracts\CanConsumeBatchMessages;
use Junges\Kafka\Contracts\BatchRepository as BatchRepositoryContract;
use Junges\Kafka\Support\Timer;

class BatchConfig implements HandlesBatchConfiguration
{
    public function __construct(
        private CallableBatchConsumer $batchConsumer,
        private Timer $timer,
        private BatchRepositoryContract $batchRepository,
        private bool $batchingEnabled = false,
        private int $batchSizeLimit = 0,
        private int $batchReleaseInterval = 0,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function isBatchingEnabled(): bool
    {
        return $this->batchingEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchSizeLimit(): int
    {
        return $this->batchSizeLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchReleaseInterval(): int
    {
        return $this->batchReleaseInterval;
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumer(): CanConsumeBatchMessages
    {
        return $this->batchConsumer;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimer(): Timer
    {
        return $this->timer;
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchRepository(): BatchRepositoryContract
    {
        return $this->batchRepository;
    }
}
