<?php

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\BatchCommitter;
use Junges\Kafka\Commit\Contracts\Committer;
use Junges\Kafka\MessageCounter;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\Message;

class BatchCommitterTest extends LaravelKafkaTestCase
{
    public function testShouldCommitMessageOnlyAfterTheBatchSizeIsReached()
    {
        $committer = $this->createMock(Committer::class);
        $committer
            ->expects($this->exactly(2))
            ->method('commitMessage')
            ->withConsecutive(
                [$this->isInstanceOf(Message::class), true],
                [$this->isInstanceOf(Message::class), true]
            );

        $batchSize = 3;
        $messageCounter = new MessageCounter(42);
        $batchCommitter = new BatchCommitter($committer, $messageCounter, $batchSize);

        for ($i = 0; $i < 7; $i++) {
            $batchCommitter->commitMessage(new Message(), true);
        }
    }

    public function testShouldAlwaysCommitDlq()
    {
        $committer = $this->createMock(Committer::class);
        $committer
            ->expects($this->exactly(2))
            ->method('commitDlq');

        $batchSize = 3;

        $messageCounter = new MessageCounter(42);
        $batchCommitter = new BatchCommitter($committer, $messageCounter, $batchSize);

        $batchCommitter->commitDlq(new Message());
        $batchCommitter->commitDlq(new Message());
    }
}
