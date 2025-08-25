<?php declare(strict_types=1);

namespace Junges\Kafka\Commit;

use Junges\Kafka\Contracts\Committer as CommitterContract;
use Junges\Kafka\Contracts\ConsumerMessage;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\TopicPartition;

class Committer implements CommitterContract
{
    public function __construct(private readonly KafkaConsumer $consumer) {}

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

    /** @throws \RdKafka\Exception */
    public function commit(mixed $messageOrOffsets = null): void
    {
        if ($messageOrOffsets instanceof ConsumerMessage) {
            $topicPartition = new TopicPartition(
                $messageOrOffsets->getTopicName(),
                $messageOrOffsets->getPartition(),
                $messageOrOffsets->getOffset() + 1
            );
            $messageOrOffsets = [$topicPartition];
        }

        $this->consumer->commit($messageOrOffsets);
    }

    /** @throws \RdKafka\Exception */
    public function commitAsync(mixed $messageOrOffsets = null): void
    {
        if ($messageOrOffsets instanceof ConsumerMessage) {
            $topicPartition = new TopicPartition(
                $messageOrOffsets->getTopicName(),
                $messageOrOffsets->getPartition(),
                $messageOrOffsets->getOffset() + 1
            );
            $messageOrOffsets = [$topicPartition];
        }

        $this->consumer->commitAsync($messageOrOffsets);
    }
}
