<?php declare(strict_types=1);

namespace Junges\Kafka\Commit;

use Junges\Kafka\Contracts\Committer;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

/**
 * @deprecated Failed messages are not consumed again with this committer. With auto commit enabled, their offsets
 *             are committed by librdkafka when the consumer unsubscribes, and with manual commit this committer
 *             is never called. Use the `retryFailedMessages` or `stopOnFailure` methods of the consumer builder,
 *             or a dead letter queue, instead.
 */
class SeekToCurrentErrorCommitter implements Committer
{
    public function __construct(private readonly KafkaConsumer $consumer, private readonly Committer $committer) {}

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

    public function commit(mixed $messageOrOffsets = null): void
    {
        $this->committer->commit($messageOrOffsets);
    }

    public function commitAsync(mixed $messageOrOffsets = null): void
    {
        $this->committer->commitAsync($messageOrOffsets);
    }
}
