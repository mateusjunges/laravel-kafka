<?php

namespace Junges\Kafka\Config;

use Junges\Kafka\Consumers\CallableBatchConsumer;
use Junges\Kafka\Contracts\BatchConfigInterface;
use Junges\Kafka\Contracts\BatchConsumerInterface;
use Junges\Kafka\Contracts\BatchRepositoryInterface;
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
    public function getBatchReleaseIntervalInMilliseconds(): int
    {
        return $this->batchReleaseIntervalInMilliseconds;
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumer(): BatchConsumerInterface
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
    public function getBatchRepository(): BatchRepositoryInterface
    {
        return $this->batchRepository;
    }
}
