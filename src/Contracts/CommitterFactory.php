<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Config\Config;
use RdKafka\KafkaConsumer;

interface CommitterFactory
{
    /**
     * Returns a new Committer instance.
     *
     * @param  \RdKafka\KafkaConsumer  $kafkaConsumer
     * @param  \Junges\Kafka\Config\Config  $config
     * @return \Junges\Kafka\Contracts\Committer
     */
    public function make(KafkaConsumer $kafkaConsumer, Config $config): Committer;
}
