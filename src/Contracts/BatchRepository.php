<?php

namespace Junges\Kafka\Contracts;

use \RdKafka\Message;
use Illuminate\Support\Collection;

interface BatchRepository
{
    /**
     * Pushes new message to batch repository
     *
     * @param Message $message
     * @return void
     */
    public function push(Message $message): void;

    /**
     * Returns all messages from batch repository
     *
     * @return Collection
     */
    public function getBatch(): Collection;

    /**
     * Returns current size of a batch
     *
     * @return int
     */
    public function getBatchSize(): int;

    /**
     * Deletes all messages from batch repository
     *
     * @return void
     */
    public function reset(): void;
}
