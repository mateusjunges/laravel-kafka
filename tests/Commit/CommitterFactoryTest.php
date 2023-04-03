<?php

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\BatchCommitter;
use Junges\Kafka\Commit\DefaultCommitterFactory;
use Junges\Kafka\Commit\KafkaCommitter;
use Junges\Kafka\Commit\NativeSleeper;
use Junges\Kafka\Commit\RetryableCommitter;
use Junges\Kafka\Commit\VoidCommitter;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\MessageCounter;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\KafkaConsumer;

class CommitterFactoryTest extends LaravelKafkaTestCase
{
    public function testShouldBuildAVoidCommitterWhenAutoCommitIsDisabled(): void
    {
        $config = new Config(
            'broker',
            ['topic'],
            'security',
            1,
            'group',
            $this->createMock(Consumer::class),
            null,
            null,
            -1,
            6,
            false
        );

        $consumer = $this->createMock(KafkaConsumer::class);

        $messageCounter = new MessageCounter(6);

        $factory = new DefaultCommitterFactory($messageCounter);

        $committer = $factory->make($consumer, $config);

        $expectedCommitter = new VoidCommitter();

        $this->assertEquals($expectedCommitter, $committer);
    }

    public function testShouldBuildARetryableBatchCommitterWhenAutoCommitIsEnabled(): void
    {
        $config = new Config(
            'broker',
            ['topic'],
            'security',
            1,
            'group',
            $this->createMock(Consumer::class),
            null,
            null,
            6,
            6,
            true
        );

        $consumer = $this->createMock(KafkaConsumer::class);

        $messageCounter = new MessageCounter(6);

        $factory = new DefaultCommitterFactory($messageCounter);

        $committer = $factory->make($consumer, $config);

        $expectedCommitter = new BatchCommitter(
            new RetryableCommitter(
                new KafkaCommitter(
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
