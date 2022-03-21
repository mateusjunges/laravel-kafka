<?php

namespace Junges\Kafka\Config;

use Junges\Kafka\BatchRepositories\BatchRepositoryInterface;
use Junges\Kafka\BatchRepositories\NullBatchRepository;
use Junges\Kafka\Consumers\BatchConsumerInterface;
use Junges\Kafka\Consumers\NullBatchConsumer;
use Junges\Kafka\Support\Timer;

final class NullBatchConfig implements BatchConfigInterface
{
    public function getBatchReleaseIntervalInMilliseconds(): int
    {
        return 0;
    }

    public function getConsumer(): BatchConsumerInterface
    {
        return new NullBatchConsumer();
    }

    public function getTimer(): Timer
    {
        return new Timer();
    }

    public function getBatchRepository(): BatchRepositoryInterface
    {
        return new NullBatchRepository();
    }

    public function getBatchSizeLimit(): int
    {
        return 0;
    }

    public function isBatchingEnabled(): bool
    {
        return false;
    }
}
