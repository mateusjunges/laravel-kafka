<?php

namespace Junges\Kafka\Commit;

use Junges\Kafka\Commit\Contracts\Committer;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

class SeekToCurrentErrorCommitter implements Committer
{
    /**
     * @var \RdKafka\KafkaConsumer
     */
    private $consumer;
    /**
     * @var \Junges\Kafka\Commit\Contracts\Committer
     */
    private $committer;
    public function __construct(KafkaConsumer $consumer, Committer $committer)
    {
        $this->consumer = $consumer;
        $this->committer = $committer;
    }

    public function commitMessage(Message $message, bool $success): void
    {
        if ($success) {
            $this->committer->commitMessage($message, $success);

            return;
        }

        $currentSubscriptions = $this->consumer->getSubscription();
        $this->consumer->unsubscribe();
        $this->consumer->subscribe($currentSubscriptions);
    }

    public function commitDlq(Message $message): void
    {
        $this->committer->commitDlq($message);
    }
}
