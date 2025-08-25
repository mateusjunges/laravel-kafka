<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\BatchCommitter;
use Junges\Kafka\Contracts\Committer;
use Junges\Kafka\MessageCounter;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\Message;

final class BatchCommitterTest extends LaravelKafkaTestCase
{
    #[Test]
    public function should_commit_message_only_after_the_batch_size_is_reached(): void
    {
        $committer = $this->createMock(Committer::class);
        $committer
            ->expects($this->exactly(2))
            ->method('commitMessage');

        $batchSize = 3;
        $messageCounter = new MessageCounter(42);
        $batchCommitter = new BatchCommitter($committer, $messageCounter, $batchSize);

        for ($i = 0; $i < 7; $i++) {
            $batchCommitter->commitMessage(new Message, true);
        }
    }

    #[Test]
    public function should_always_commit_dlq(): void
    {
        $committer = $this->createMock(Committer::class);
        $committer
            ->expects($this->exactly(2))
            ->method('commitDlq');

        $batchSize = 3;

        $messageCounter = new MessageCounter(42);
        $batchCommitter = new BatchCommitter($committer, $messageCounter, $batchSize);

        $batchCommitter->commitDlq(new Message);
        $batchCommitter->commitDlq(new Message);
    }
}
