<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\RetryableCommitter;
use Junges\Kafka\Tests\FailingCommitter;
use Junges\Kafka\Tests\Fakes\FakeSleeper;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\Exception as RdKafkaException;
use RdKafka\Message;

final class RetryableCommitterTest extends LaravelKafkaTestCase
{
    public function testItShouldRetryToCommit(): void
    {
        $exception = new RdKafkaException("Something went wrong", RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT);
        $failingCommitter = new FailingCommitter($exception, 3);
        $retryableCommitter = new RetryableCommitter($failingCommitter, new FakeSleeper());

        $retryableCommitter->commitMessage(new Message(), false);
        $retryableCommitter->commitDlq(new Message());

        $this->assertEquals(4, $failingCommitter->getTimesTriedToCommitMessage());
        $this->assertEquals(4, $failingCommitter->getTimesTriedToCommitDlq());
    }

    public function testItShouldRetryOnlyUpToTheMaximumNumberOfRetries(): void
    {
        $expectedException = new RdKafkaException("Something went wrong", RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT);
        $failingCommitter = new FailingCommitter($expectedException, 99);
        $retryableCommitter = new RetryableCommitter($failingCommitter, new FakeSleeper(), 4);

        $commitMessageException = null;

        try {
            $retryableCommitter->commitMessage(new Message(), false);
        } catch (RdKafkaException $exception) {
            $commitMessageException = $exception;
        }

        $commitDlqException = null;

        try {
            $retryableCommitter->commitDlq(new Message());
        } catch (RdKafkaException $exception) {
            $commitDlqException = $exception;
        }

        // first execution + 4 retries = 5 executions
        $this->assertEquals(5, $failingCommitter->getTimesTriedToCommitMessage());
        $this->assertSame($expectedException, $commitMessageException);

        $this->assertEquals(5, $failingCommitter->getTimesTriedToCommitDlq());
        $this->assertSame($expectedException, $commitDlqException);
    }

    public function testItShouldProgressivelyWaitForTheNextRetry(): void
    {
        $expectedException = new RdKafkaException("Something went wrong", RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT);

        $sleeper = new FakeSleeper();
        $failingCommitter = new FailingCommitter($expectedException, 99);
        $retryableCommitter = new RetryableCommitter($failingCommitter, $sleeper, 6);

        try {
            $retryableCommitter->commitMessage(new Message(), true);
        } catch (RdKafkaException $exception) {
        }

        $expectedSleeps = [1e6, 2e6, 4e6, 8e6, 16e6, 32e6];
        $this->assertEquals($expectedSleeps, $sleeper->getSleeps());
    }
}
