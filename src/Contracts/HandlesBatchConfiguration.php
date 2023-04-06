<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Support\Timer;

interface HandlesBatchConfiguration
{
    /** Returns capacity of a batch. */
    public function getBatchSizeLimit(): int;

    /** Returns whether batching is enabled or not. */
    public function isBatchingEnabled(): bool;

    /** Returns interval in milliseconds in which messages will be released from batch if it is not full. */
    public function getBatchReleaseInterval(): int;

    /** Returns BatchConsumerInterface implementation instance. */
    public function getConsumer(): BatchMessageConsumer;

    /** Returns a timer that determines whether batch interval has passed or not. */
    public function getTimer(): Timer;

    /** Returns BatchRepositoryInterface implementation instance. */
    public function getBatchRepository(): BatchRepository;
}
