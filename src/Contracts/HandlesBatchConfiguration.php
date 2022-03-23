<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Support\Timer;

interface HandlesBatchConfiguration
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
     * Returns interval in milliseconds in which messages will be released from batch if it is not full
     *
     * @return int
     */
    public function getBatchReleaseInterval(): int;

    /**
     * Returns BatchConsumerInterface implementation instance
     *
     * @return CanConsumeBatchMessages
     */
    public function getConsumer(): CanConsumeBatchMessages;

    /**
     * Returns a timer that determines whether batch interval has passed or not
     *
     * @return Timer
     */
    public function getTimer(): Timer;

    /**
     * Returns BatchRepositoryInterface implementation instance
     *
     * @return BatchRepository
     */
    public function getBatchRepository(): BatchRepository;
}
