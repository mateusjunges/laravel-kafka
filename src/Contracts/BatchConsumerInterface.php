<?php

namespace Junges\Kafka\Contracts;

use Illuminate\Support\Collection;

interface BatchConsumerInterface
{
    /**
     * Handles messages released from batch repository
     *
     * @param Collection $collection
     * @return void
     */
    public function handle(Collection $collection): void;
}
