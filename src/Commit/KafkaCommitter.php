<?php

namespace Junges\Kafka\Commit;

use Junges\Kafka\Commit\Contracts\Committer;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

class KafkaCommitter implements Committer
{
    /**
     * @var \RdKafka\KafkaConsumer
     */
    private $consumer;
    public function __construct(KafkaConsumer $consumer)
    {
        $this->consumer = $consumer;
    }

    /**
     * @throws \RdKafka\Exception
     */
    public function commitMessage(Message $message, bool $success): void
    {
        $this->consumer->commit($message);
    }

    /**
     * @throws \RdKafka\Exception
     */
    public function commitDlq(Message $message): void
    {
        $this->consumer->commit($message);
    }
}
