<?php

namespace Junges\Kafka\Config;

use Junges\Kafka\Consumers\CallableBatchConsumer;
use Junges\Kafka\Contracts\BatchRepository as BatchRepositoryContract;
use Junges\Kafka\Contracts\CanConsumeBatchMessages;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;
use Junges\Kafka\Support\Timer;

class BatchConfig implements HandlesBatchConfiguration
{
    /**
     * @var \Junges\Kafka\Consumers\CallableBatchConsumer
     */
    private $batchConsumer;
    /**
     * @var \Junges\Kafka\Support\Timer
     */
    private $timer;
    /**
     * @var BatchRepositoryContract
     */
    private $batchRepository;
    /**
     * @var bool
     */
    private $batchingEnabled = false;
    /**
     * @var int
     */
    private $batchSizeLimit = 0;
    /**
     * @var int
     */
    private $batchReleaseInterval = 0;
    public function __construct(CallableBatchConsumer $batchConsumer, Timer $timer, BatchRepositoryContract $batchRepository, bool $batchingEnabled = false, int $batchSizeLimit = 0, int $batchReleaseInterval = 0)
    {
        $this->batchConsumer = $batchConsumer;
        $this->timer = $timer;
        $this->batchRepository = $batchRepository;
        $this->batchingEnabled = $batchingEnabled;
        $this->batchSizeLimit = $batchSizeLimit;
        $this->batchReleaseInterval = $batchReleaseInterval;
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
