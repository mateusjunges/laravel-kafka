<?php

namespace Junges\Kafka\BatchRepositories;

use \RdKafka\Message;
use Illuminate\Support\Collection;

interface BatchRepositoryInterface
{
    public function push(Message $message): void;

    public function getBatch(): Collection;

    public function getBatchSize(): int;

    public function reset(): void;
}
