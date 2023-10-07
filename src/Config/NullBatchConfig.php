<?php declare(strict_types=1);

namespace Junges\Kafka\Config;

use Junges\Kafka\BatchRepositories\NullBatchRepository;
use Junges\Kafka\Consumers\NullBatchConsumer;
use Junges\Kafka\Contracts\BatchMessageConsumer;
use Junges\Kafka\Contracts\BatchRepository;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;
use Junges\Kafka\Support\Timer;

class NullBatchConfig implements HandlesBatchConfiguration
{
    public function getBatchReleaseInterval(): int
    {
        return 0;
    }

    public function getConsumer(): BatchMessageConsumer
    {
        return new NullBatchConsumer();
    }

    public function getTimer(): Timer
    {
        return new Timer();
    }

    public function getBatchRepository(): BatchRepository
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
