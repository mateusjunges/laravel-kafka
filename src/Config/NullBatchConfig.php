<?php

namespace Junges\Kafka\Config;

use Junges\Kafka\BatchRepositories\NullBatchRepository;
use Junges\Kafka\Consumers\NullBatchConsumer;
use Junges\Kafka\Contracts\BatchConfigInterface;
use Junges\Kafka\Contracts\BatchConsumerInterface;
use Junges\Kafka\Contracts\BatchRepositoryInterface;
use Junges\Kafka\Support\Timer;

class NullBatchConfig implements BatchConfigInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBatchReleaseIntervalInMilliseconds(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumer(): BatchConsumerInterface
    {
        return new NullBatchConsumer();
    }

    /**
     * {@inheritdoc}
     */
    public function getTimer(): Timer
    {
        return new Timer();
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchRepository(): BatchRepositoryInterface
    {
        return new NullBatchRepository();
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchSizeLimit(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isBatchingEnabled(): bool
    {
        return false;
    }
}
