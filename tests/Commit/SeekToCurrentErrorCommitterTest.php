<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\SeekToCurrentErrorCommitter;
use Junges\Kafka\Contracts\Committer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

final class SeekToCurrentErrorCommitterTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_should_commit_on_success(): void
    {
        $mockedKafkaConsumer = $this->createMock(KafkaConsumer::class);

        $mockedCommitter = $this->createMock(Committer::class);
        $mockedCommitter->expects($this->once())
            ->method('commitMessage')
            ->with($this->isInstanceOf(Message::class), true);

        $seekToCurrentErrorCommitter = new SeekToCurrentErrorCommitter($mockedKafkaConsumer, $mockedCommitter);

        $seekToCurrentErrorCommitter->commitMessage(new Message, true);
    }

    #[Test]
    public function it_should_not_commit_and_resubscribe_on_error(): void
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

        $seekToCurrentErrorCommitter->commitMessage(new Message, false);
    }

    #[Test]
    public function it_passes_dlq_commits(): void
    {
        $mockedKafkaConsumer = $this->createMock(KafkaConsumer::class);

        $mockedCommitter = $this->createMock(Committer::class);
        $mockedCommitter->expects($this->once())
            ->method('commitDlq')
            ->with($this->isInstanceOf(Message::class));

        $seekToCurrentErrorCommitter = new SeekToCurrentErrorCommitter($mockedKafkaConsumer, $mockedCommitter);

        $seekToCurrentErrorCommitter->commitDlq(new Message, true);
    }
}
