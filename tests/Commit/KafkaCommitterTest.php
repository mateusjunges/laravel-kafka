<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\Committer;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Consumers\CallableConsumer;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

final class KafkaCommitterTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_can_commit(): void
    {
        $kafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('commit')->once()
            ->andReturnSelf();

        $this->app->bind(KafkaConsumer::class, function () use ($kafkaConsumer) {
            return $kafkaConsumer->getMock();
        });

        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            groupId: 'groupId'
        );

        $conf = new Conf;

        foreach ($config->getConsumerOptions() as $key => $value) {
            $conf->set($key, $value);
        }

        $kafkaCommitter = new Committer(app(KafkaConsumer::class, [
            'conf' => $conf,
        ]));

        $kafkaCommitter->commitMessage(new Message, true);
    }

    #[Test]
    public function it_can_commit_to_dlq(): void
    {
        $kafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('commit')->once()
            ->andReturnSelf();

        $this->app->bind(KafkaConsumer::class, function () use ($kafkaConsumer) {
            return $kafkaConsumer->getMock();
        });

        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            groupId: 'groupId'
        );

        $conf = new Conf;

        foreach ($config->getConsumerOptions() as $key => $value) {
            $conf->set($key, $value);
        }

        $kafkaCommitter = new Committer(app(KafkaConsumer::class, [
            'conf' => $conf,
        ]));

        $kafkaCommitter->commitDlq(new Message);
    }

    #[Test]
    public function it_allows_manual_commits_in_manual_commit_mode(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 5;
        $message->partition = 1;
        $message->headers = [];

        $commitCalled = false;

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->andReturnUsing(function () use (&$commitCalled) {
                $commitCalled = true;

                return null;
            })
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $handlerCalled = false;

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) use (&$handlerCalled) {
                $handlerCalled = true;
                // This should actually commit now!
                $consumer->commit($message);
            },
            []
        );

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            maxMessages: 1,
            autoCommit: false
        );

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();

        $this->assertTrue($handlerCalled);
        $this->assertTrue($commitCalled, 'Manual commit should work in manual commit mode');
    }

    #[Test]
    public function it_disables_auto_commits_in_manual_commit_mode(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 5;
        $message->partition = 1;
        $message->headers = [];

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->never()
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $handlerCalled = false;

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) use (&$handlerCalled) {
                $handlerCalled = true;
                // Don't manually commit, should result in no commits
            },
            []
        );

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            maxMessages: 1,
            autoCommit: false
        );

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();

        $this->assertTrue($handlerCalled);
    }

    #[Test]
    public function it_enables_auto_commits_in_auto_commit_mode(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 5;
        $message->partition = 1;
        $message->headers = [];

        $autoCommitCalled = false;

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->andReturnUsing(function () use (&$autoCommitCalled) {
                $autoCommitCalled = true;

                return null;
            })
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $handlerCalled = false;

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) use (&$handlerCalled) {
                $handlerCalled = true;
                // Don't manually commit, auto-commit should handle it
            },
            []
        );

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            maxMessages: 1,
            autoCommit: true
        );

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();

        $this->assertTrue($handlerCalled);
        $this->assertTrue($autoCommitCalled, 'Auto-commit should work in auto-commit mode');
    }
}
