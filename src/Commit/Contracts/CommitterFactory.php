<?php

namespace Junges\Kafka\Commit\Contracts;

use Junges\Kafka\Config\Config;
use RdKafka\KafkaConsumer;

interface CommitterFactory
{
    public function make(KafkaConsumer $kafkaConsumer, Config $config): Committer;
}
