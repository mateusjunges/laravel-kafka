<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\SeekToCurrentErrorCommitter;
use Junges\Kafka\Contracts\Committer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

final class SeekToCurrentErrorCommitterTest extends LaravelKafkaTestCase
{
    public function testItShouldCommitOnSuccess(): void
    {
        $mockedKafkaConsumer = $this->createMock(KafkaConsumer::class);

        $mockedCommitter = $this->createMock(Committer::class);
        $mockedCommitter->expects($this->once())
            ->method('commitMessage')
            ->with($this->isInstanceOf(Message::class), true);

        $seekToCurrentErrorCommitter = new SeekToCurrentErrorCommitter($mockedKafkaConsumer, $mockedCommitter);

        $seekToCurrentErrorCommitter->commitMessage(new Message(), true);
    }

    public function testItShouldNotCommitAndResubscribeOnError(): void
    {
        $mockedKafkaConsumer = $this->createMock(KafkaConsumer::class);
        $mockedKafkaConsumer->expects($this->once())
            ->method('getSubscription')
            ->willReturn(['test-topic']);
        $mockedKafkaConsumer->expects($this->once())
            ->method('unsubscribe');
        $mockedKafkaConsumer->expects($this->once())
            ->method('subscribe')
            ->with(['test-topic']);

        $mockedCommitter = $this->createMock(Committer::class);
        $mockedCommitter->expects($this->never())
            ->method('commitMessage');

        $seekToCurrentErrorCommitter = new SeekToCurrentErrorCommitter($mockedKafkaConsumer, $mockedCommitter);

        $seekToCurrentErrorCommitter->commitMessage(new Message(), false);
    }

    public function testItPassesDlqCommits(): void
    {
        $mockedKafkaConsumer = $this->createMock(KafkaConsumer::class);

        $mockedCommitter = $this->createMock(Committer::class);
        $mockedCommitter->expects($this->once())
                        ->method('commitDlq')
                        ->with($this->isInstanceOf(Message::class));

        $seekToCurrentErrorCommitter = new SeekToCurrentErrorCommitter($mockedKafkaConsumer, $mockedCommitter);

        $seekToCurrentErrorCommitter->commitDlq(new Message(), true);
    }
}
