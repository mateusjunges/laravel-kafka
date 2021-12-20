<?php

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\BatchCommitter;
use Junges\Kafka\Commit\DefaultCommitterFactory;
use Junges\Kafka\Commit\KafkaCommitter;
use Junges\Kafka\Commit\NativeSleeper;
use Junges\Kafka\Commit\RetryableCommitter;
use Junges\Kafka\Commit\VoidCommitter;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Message\MessageCounter;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\KafkaConsumer;

class CommitterFactoryTest extends LaravelKafkaTestCase
{
    public function testShouldBuildARetryableBatchCommitterWhenAutoCommitIsDisable(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            handler: $this->createMock(Handler::class),
            sasl: null,
            dlq: null,
            maxMessages: -1,
            maxCommitRetries: 6,
            autoCommit: false
        );

        $handler = $this->createMock(KafkaConsumer::class);

        $messageCounter = new MessageCounter(6);

        $factory = new DefaultCommitterFactory($messageCounter);

        $committer = $factory->make($handler, $config);

        $expectedCommitter = new BatchCommitter(
            new RetryableCommitter(
                new KafkaCommitter(
                    $handler
                ),
                new NativeSleeper(),
                $config->getMaxCommitRetries()
            ),
            $messageCounter,
            $config->getCommit()
        );

        $this->assertEquals($expectedCommitter, $committer);
    }

    public function testShouldBuildAVoidCommitterWhenAutoCommitIsEnabled(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            handler: $this->createMock(Handler::class),
            sasl: null,
            dlq: null,
            maxMessages: 6,
            maxCommitRetries: 6,
            autoCommit: true
        );

        $handler = $this->createMock(KafkaConsumer::class);

        $messageCounter = new MessageCounter(6);

        $factory = new DefaultCommitterFactory($messageCounter);

        $committer = $factory->make($handler, $config);

        $this->assertInstanceOf(VoidCommitter::class, $committer);
    }
}
