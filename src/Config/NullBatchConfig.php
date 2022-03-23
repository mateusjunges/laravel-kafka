<?php

namespace Junges\Kafka\Config;

use Junges\Kafka\BatchRepositories\NullBatchRepository;
use Junges\Kafka\Consumers\NullBatchConsumer;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;
use Junges\Kafka\Contracts\CanConsumeBatchMessages;
use Junges\Kafka\Contracts\BatchRepository;
use Junges\Kafka\Support\Timer;

class NullBatchConfig implements HandlesBatchConfiguration
{
    /**
     * {@inheritdoc}
     */
    public function getBatchReleaseInterval(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumer(): CanConsumeBatchMessages
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
    public function getBatchRepository(): BatchRepository
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
