<?php

namespace Junges\Kafka;

class Cluster
{
    public function __construct(
        private string $brokers,
        private string $compression,

    )
    {
    }
}