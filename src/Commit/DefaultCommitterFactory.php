<?php

namespace Junges\Kafka\Commit;

use Junges\Kafka\Commit\Contracts\Committer;
use Junges\Kafka\Commit\Contracts\CommitterFactory;
use Junges\Kafka\Config\Config;
use Junges\Kafka\MessageCounter;
use RdKafka\KafkaConsumer;

class DefaultCommitterFactory implements CommitterFactory
{
    public function __construct(private MessageCounter $messageCounter)
    {
    }

    public function make(KafkaConsumer $kafkaConsumer, Config $config): Committer
    {
        if ($config->isAutoCommit()) {
            return new VoidCommitter();
        }

        return new BatchCommitter(
            new RetryableCommitter(
                new KafkaCommitter(
                    $kafkaConsumer
                ),
                new NativeSleeper(),
                $config->getMaxCommitRetries()
            ),
            $this->messageCounter,
            $config->getCommit()
        );
    }
}
