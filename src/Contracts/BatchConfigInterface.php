<?php

namespace Junges\Kafka\Config;

use Junges\Kafka\BatchRepositories\BatchRepositoryInterface;
use Junges\Kafka\Consumers\BatchConsumerInterface;
use Junges\Kafka\Support\Timer;

interface BatchConfigInterface
{
    public function getBatchSizeLimit(): int;

    public function isBatchingEnabled(): bool;

    public function getBatchReleaseIntervalInMilliseconds(): int;

    public function getConsumer(): BatchConsumerInterface;

    public function getTimer(): Timer;

    public function getBatchRepository(): BatchRepositoryInterface;
}
