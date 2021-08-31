<?php

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\BatchCommitter;
use Junges\Kafka\Commit\Contracts\Committer;
use Junges\Kafka\MessageCounter;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class BatchCommitterTest extends LaravelKafkaTestCase
{
    public function testShouldCommitMessageOnlyAfterTheBatchSizeIsReached()
    {
        $committer = $this->createMock(Committer::class);
        $committer
            ->expects($this->exactly(2))
            ->method('commitMessage');

        $batchSize = 3;
        $messageCounter = new MessageCounter(42);
        $batchCommitter = new BatchCommitter($committer, $messageCounter, $batchSize);

        for ($i = 0; $i < 7; $i++) {
            $batchCommitter->commitMessage();
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

        $batchCommitter->commitDlq();
        $batchCommitter->commitDlq();
    }
}
