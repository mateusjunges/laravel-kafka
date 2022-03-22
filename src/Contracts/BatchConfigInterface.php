<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Support\Timer;

interface BatchConfigInterface
{
    /**
     * Returns capacity of a batch
     *
     * @return int
     */
    public function getBatchSizeLimit(): int;

    /**
     * Returns whether batching is enabled or not
     *
     * @return bool
     */
    public function isBatchingEnabled(): bool;

    /**
     * Returns interval in which messages will be released from batch if it is not full
     *
     * @return int
     */
    public function getBatchReleaseIntervalInMilliseconds(): int;

    /**
     * Returns BatchConsumerInterface implementation instance
     *
     * @return BatchConsumerInterface
     */
    public function getConsumer(): BatchConsumerInterface;

    /**
     * Returns a timer that determines whether batch interval has passed or not
     *
     * @return Timer
     */
    public function getTimer(): Timer;

    /**
     * Returns BatchRepositoryInterface implementation instance
     *
     * @return BatchRepositoryInterface
     */
    public function getBatchRepository(): BatchRepositoryInterface;
}
