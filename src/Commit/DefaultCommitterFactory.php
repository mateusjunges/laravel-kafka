<?php declare(strict_types=1);

namespace Junges\Kafka\Commit;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Committer as CommitterContract;
use Junges\Kafka\Contracts\CommitterFactory;
use RdKafka\KafkaConsumer;

class DefaultCommitterFactory implements CommitterFactory
{
    public function make(KafkaConsumer $kafkaConsumer, Config $config): CommitterContract
    {
        return new Committer($kafkaConsumer);
    }
}
