<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\BatchCommitter;
use Junges\Kafka\Commit\Committer;
use Junges\Kafka\Commit\DefaultCommitterFactory;
use Junges\Kafka\Commit\NativeSleeper;
use Junges\Kafka\Commit\RetryableCommitter;
use Junges\Kafka\Commit\VoidCommitter;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\MessageCounter;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaConsumer;

final class CommitterFactoryTest extends LaravelKafkaTestCase
{
    #[Test]
    public function should_build_a_void_committer_when_auto_commit_is_disabled(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $this->createMock(Consumer::class),
            sasl: null,
            dlq: null,
            maxMessages: -1,
            maxCommitRetries: 6,
            autoCommit: false
        );

        $consumer = $this->createMock(KafkaConsumer::class);

        $messageCounter = new MessageCounter(6);

        $factory = new DefaultCommitterFactory($messageCounter);

        $committer = $factory->make($consumer, $config);

        $expectedCommitter = new VoidCommitter();

        $this->assertEquals($expectedCommitter, $committer);
    }

    #[Test]
    public function should_build_a_retryable_batch_committer_when_auto_commit_is_enabled(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $this->createMock(Consumer::class),
            sasl: null,
            dlq: null,
            maxMessages: 6,
            maxCommitRetries: 6,
            autoCommit: true
        );

        $consumer = $this->createMock(KafkaConsumer::class);

        $messageCounter = new MessageCounter(6);

        $factory = new DefaultCommitterFactory($messageCounter);

        $committer = $factory->make($consumer, $config);

        $expectedCommitter = new BatchCommitter(
            new RetryableCommitter(
                new Committer(
                    $consumer
                ),
                new NativeSleeper(),
                $config->getMaxCommitRetries()
            ),
            $messageCounter,
            $config->getCommit()
        );

        $this->assertEquals($expectedCommitter, $committer);
    }
}
