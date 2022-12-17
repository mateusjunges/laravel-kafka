<?php

namespace Junges\Kafka\Commit\Contracts;

use Junges\Kafka\Config\Config;
use RdKafka\KafkaConsumer;

interface CommitterFactory
{
    /**
     * Returns a new Committer instance.
     */
    public function make(KafkaConsumer $kafkaConsumer, Config $config): Committer;
}
