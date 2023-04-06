<?php declare(strict_types=1);

namespace Junges\Kafka\Commit;

use Junges\Kafka\Contracts\Committer as CommitterContract;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

class Committer implements CommitterContract
{
    public function __construct(private readonly KafkaConsumer $consumer)
    {
    }

    /** @throws \RdKafka\Exception  */
    public function commitMessage(Message $message, bool $success): void
    {
        $this->consumer->commit($message);
    }

    /** @throws \RdKafka\Exception */
    public function commitDlq(Message $message): void
    {
        $this->consumer->commit($message);
    }
}
