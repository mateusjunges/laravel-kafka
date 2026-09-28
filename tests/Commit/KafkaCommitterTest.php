<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Commit;

use Junges\Kafka\Commit\Committer;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Consumers\CallableConsumer;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\KafkaConsumerTopic;
use RdKafka\Message;

final class KafkaCommitterTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_commits_the_offset_after_a_consumer_message(): void
    {
        $kafkaConsumer = $this->mockKafkaConsumer()
            ->shouldReceive('commit')->once()
            ->with(m::on(fn (array $offsets) => count($offsets) === 1
                && $offsets[0]->getTopic() === 'topic'
                && $offsets[0]->getPartition() === 1
                && $offsets[0]->getOffset() === 11))
            ->andReturnSelf();

        $this->app->bind(KafkaConsumer::class, fn () => $kafkaConsumer->getMock());

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

        $kafkaCommitter->commit(new ConsumedMessage('topic', 1, [], null, null, 10, null));
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

        $mockedKafkaConsumer = $this->mockKafkaConsumer()
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
            securityProtocol: 'PLAINTEXT',
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

        $mockedKafkaConsumer = $this->mockKafkaConsumer()
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
            securityProtocol: 'PLAINTEXT',
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
    public function it_stores_offsets_for_the_background_commit_in_auto_commit_mode(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 5;
        $message->partition = 1;
        $message->headers = [];

        $mockedTopic = m::mock(KafkaConsumerTopic::class);
        $mockedTopic->shouldReceive('offsetStore')->once()->with(1, 5);

        $mockedKafkaConsumer = $this->mockKafkaConsumer()
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('newTopic')
            ->andReturn($mockedTopic)
            ->shouldReceive('commit')
            ->never()
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
            securityProtocol: 'PLAINTEXT',
            groupId: 'group',
            consumer: $fakeHandler,
            maxMessages: 1,
            autoCommit: true
        );

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();

        $this->assertTrue($handlerCalled);
    }
}
