<?php declare(strict_types=1);

namespace Junges\Kafka\Commit;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Committer as CommitterContract;
use Junges\Kafka\Contracts\CommitterFactory;
use Junges\Kafka\MessageCounter;
use RdKafka\KafkaConsumer;

class DefaultCommitterFactory implements CommitterFactory
{
    public function __construct(private readonly MessageCounter $messageCounter)
    {
    }

    public function make(KafkaConsumer $kafkaConsumer, Config $config): CommitterContract
    {
        if (! $config->isAutoCommit()) {
            return new VoidCommitter();
        }

        return new BatchCommitter(
            new RetryableCommitter(
                new Committer(
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
