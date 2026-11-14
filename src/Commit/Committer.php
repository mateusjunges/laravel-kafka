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

    /** @throws \RdKafka\Exception */
    public function commit(ConsumerMessage|Message|array|null $messageOrOffsets = null): void
    {
        $this->consumer->commit($this->toOffsets($messageOrOffsets));
    }

    /** @throws \RdKafka\Exception */
    public function commitAsync(ConsumerMessage|Message|array|null $messageOrOffsets = null): void
    {
        $this->consumer->commitAsync($this->toOffsets($messageOrOffsets));
    }

    /**
     * librdkafka commits raw messages and topic partitions. A consumed message is committed as
     * the offset right after it, the next message the consumer group should read.
     *
     * @return Message|list<TopicPartition>|null
     */
    private function toOffsets(ConsumerMessage|Message|array|null $messageOrOffsets): Message|array|null
    {
        if (! $messageOrOffsets instanceof ConsumerMessage) {
            return $messageOrOffsets;
        }

        return [new TopicPartition(
            $messageOrOffsets->getTopicName(),
            $messageOrOffsets->getPartition(),
            $messageOrOffsets->getOffset() + 1,
        )];
    }
}
