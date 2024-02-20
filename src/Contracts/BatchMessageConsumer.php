<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use Illuminate\Support\Collection;

interface BatchMessageConsumer
{
    /** Handles messages released from batch repository. */
    public function handle(Collection $collection, MessageConsumer $consumer): void;
}
