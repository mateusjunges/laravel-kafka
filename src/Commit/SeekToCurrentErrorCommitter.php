<?php declare(strict_types=1);

namespace Junges\Kafka\Commit;

use Junges\Kafka\Contracts\Committer;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

class SeekToCurrentErrorCommitter implements Committer
{
    public function __construct(private readonly KafkaConsumer $consumer, private readonly Committer $committer)
    {
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
