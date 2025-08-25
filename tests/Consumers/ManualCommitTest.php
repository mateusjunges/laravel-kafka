<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Exception;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Consumers\CallableConsumer;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\TopicPartition;
use Throwable;

final class ManualCommitTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_can_commit_manually_with_consumer_message(): void
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
        $committedOffsets = [];

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->with(m::on(function ($topicPartitions) use (&$commitCalled, &$committedOffsets) {
                $commitCalled = true;

                if (is_array($topicPartitions) && count($topicPartitions) === 1) {
                    $tp = $topicPartitions[0];

                    if ($tp instanceof TopicPartition) {
                        $committedOffsets = [
                            'topic' => $tp->getTopic(),
                            'partition' => $tp->getPartition(),
                            'offset' => $tp->getOffset(),
                        ];

                        return true;
                    }
                }

                return false;
            }))
            ->andReturn()
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $handlerCalled = false;
        $manualCommitCalled = false;

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) use (&$handlerCalled, &$manualCommitCalled) {
                $handlerCalled = true;
                $consumer->commit($message);
                $manualCommitCalled = true;
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
        $this->assertTrue($manualCommitCalled);
        $this->assertTrue($commitCalled);
        $this->assertEquals('test-topic', $committedOffsets['topic']);
        $this->assertEquals(1, $committedOffsets['partition']);
        $this->assertEquals(6, $committedOffsets['offset']); // offset + 1
    }

    #[Test]
    public function it_can_commit_async_with_consumer_message(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 10;
        $message->partition = 2;
        $message->headers = [];

        $commitAsyncCalled = false;

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commitAsync')
            ->with(m::on(function ($topicPartitions) use (&$commitAsyncCalled) {
                $commitAsyncCalled = true;

                return is_array($topicPartitions) && count($topicPartitions) === 1
                    && $topicPartitions[0] instanceof TopicPartition;
            }))
            ->andReturn()
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) {
                $consumer->commitAsync($message);
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

        $this->assertTrue($commitAsyncCalled);
    }

    #[Test]
    public function it_can_commit_without_parameters(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
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
            ->with(null)
            ->andReturnUsing(function () use (&$commitCalled) {
                $commitCalled = true;

                return null;
            })
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) {
                // Commit all current assignment offsets
                $consumer->commit();
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

        $this->assertTrue($commitCalled);
    }

    #[Test]
    public function it_can_commit_with_rdkafka_message(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $commitCalled = false;
        $committedMessage = null;

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->with(m::on(function ($msg) use (&$commitCalled, &$committedMessage, $message) {
                $commitCalled = true;
                $committedMessage = $msg;

                return $msg === $message;
            }))
            ->andReturn()
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) {
                $rdkafkaMessage = new Message;
                $rdkafkaMessage->topic_name = $message->getTopicName();
                $rdkafkaMessage->partition = $message->getPartition();
                $rdkafkaMessage->offset = $message->getOffset();

                $consumer->commit($rdkafkaMessage);
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

        $this->assertTrue($commitCalled);
        $this->assertInstanceOf(Message::class, $committedMessage);
    }

    #[Test]
    public function it_does_not_auto_commit_when_manual_commit_is_enabled(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->never() // Should never be called for auto-commit
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) {
                // Process message but don't commit, with manual commit disabled, no auto-commit should happen
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
    }

    #[Test]
    public function it_converts_consumer_message_to_topic_partition_correctly(): void
    {
        $consumerMessage = new ConsumedMessage(
            topicName: 'test-topic',
            partition: 3,
            headers: [],
            body: 'test message',
            key: 'test-key',
            offset: 15,
            timestamp: time()
        );

        $commitCalled = false;
        $topicPartitionData = [];

        $dummyMessage = new Message;
        $dummyMessage->err = 0;
        $dummyMessage->topic_name = 'test-topic';
        $dummyMessage->partition = 1;
        $dummyMessage->offset = 0;
        $dummyMessage->payload = '{}';
        $dummyMessage->headers = [];

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($dummyMessage)
            ->shouldReceive('commit')
            ->with(m::on(function ($topicPartitions) use (&$commitCalled, &$topicPartitionData) {
                $commitCalled = true;

                if (is_array($topicPartitions) && count($topicPartitions) === 1) {
                    $tp = $topicPartitions[0];

                    if ($tp instanceof TopicPartition) {
                        $topicPartitionData = [
                            'topic' => $tp->getTopic(),
                            'partition' => $tp->getPartition(),
                            'offset' => $tp->getOffset(),
                        ];

                        return true;
                    }
                }

                return false;
            }))
            ->andReturn()
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) use ($consumerMessage) {
                // Use the specific ConsumerMessage for commit
                $consumer->commit($consumerMessage);
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

        $this->assertTrue($commitCalled);
        $this->assertEquals('test-topic', $topicPartitionData['topic']);
        $this->assertEquals(3, $topicPartitionData['partition']);
        $this->assertEquals(16, $topicPartitionData['offset']);
    }

    #[Test]
    public function it_handles_commit_errors_gracefully(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $exceptionThrown = false;

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->andThrow(new \RdKafka\Exception('Commit failed', RD_KAFKA_RESP_ERR_INVALID_CONFIG))
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) use (&$exceptionThrown) {
                try {
                    $consumer->commit($message);
                } catch (Throwable $e) {
                    $exceptionThrown = true;
                }
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

        $this->assertTrue($exceptionThrown);
    }

    #[Test]
    public function it_ignores_no_offset_commit_errors(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $noExceptionThrown = true;

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->andThrow(new \RdKafka\Exception('No offset', RD_KAFKA_RESP_ERR__NO_OFFSET))
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) use (&$noExceptionThrown) {
                try {
                    $consumer->commit($message);
                } catch (\RdKafka\Exception $e) {
                    $noExceptionThrown = false;
                }
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

        // RD_KAFKA_RESP_ERR__NO_OFFSET errors should be ignored
        $this->assertTrue($noExceptionThrown);
    }

    #[Test]
    public function it_auto_commits_when_auto_commit_is_enabled(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
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
            ->with($message) // Auto-commit should pass the original message
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
        $this->assertTrue($autoCommitCalled);
    }

    #[Test]
    public function it_allows_manual_commit_to_override_auto_commit_behavior(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 5;
        $message->partition = 1;
        $message->headers = [];

        $manualCommitCalled = false;

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->with(m::type('array')) // Manual commit converts to TopicPartition array
            ->andReturnUsing(function () use (&$manualCommitCalled) {
                $manualCommitCalled = true;

                return null;
            })
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $handlerCalled = false;

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) use (&$handlerCalled) {
                $handlerCalled = true;
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
        $this->assertTrue($manualCommitCalled);
    }

    #[Test]
    public function it_handles_handler_exceptions_differently_in_auto_vs_manual_commit(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $mockedKafkaConsumerManualCommit = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->never() // Should not commit on exception in manual mode
            ->shouldReceive('commitAsync')
            ->never()
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumerManualCommit);
        $this->mockProducer();

        $manualCommitHandlerCalled = false;

        $fakeHandlerManualCommit = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) use (&$manualCommitHandlerCalled) {
                $manualCommitHandlerCalled = true;

                throw new Exception('Processing failed');
            },
            []
        );

        $manualCommitConfig = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandlerManualCommit,
            maxMessages: 1,
            autoCommit: false
        );

        $manualCommitConsumer = new Consumer($manualCommitConfig, new JsonDeserializer);

        try {
            $manualCommitConsumer->consume();
        } catch (Exception) {
        }

        $this->assertTrue($manualCommitHandlerCalled);
    }

    #[Test]
    public function it_uses_same_commit_infrastructure_for_both_modes(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 7;
        $message->partition = 2;
        $message->headers = [];

        $commitCallsLog = [];

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message, $message)
            ->shouldReceive('commit')
            ->andReturnUsing(function ($params) use (&$commitCallsLog) {
                $commitCallsLog[] = [
                    'type' => 'commit',
                    'params' => $params,
                ];

                return null;
            })
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        // Test 1: Auto-commit mode
        $autoCommitHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) {
                // Just process, auto-commit will handle it
            },
            []
        );

        $autoCommitConfig = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $autoCommitHandler,
            maxMessages: 1,
            autoCommit: true
        );

        $autoCommitConsumer = new Consumer($autoCommitConfig, new JsonDeserializer);
        $autoCommitConsumer->consume();

        $manualCommitHandler = new CallableConsumer(
            function (ConsumerMessage $message, Consumer $consumer) {
                $consumer->commit($message);
            },
            []
        );

        $manualCommitConfig = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $manualCommitHandler,
            maxMessages: 1,
            autoCommit: false
        );

        $manualCommitConsumer = new Consumer($manualCommitConfig, new JsonDeserializer);
        $manualCommitConsumer->consume();

        $this->assertCount(2, $commitCallsLog);
        $this->assertInstanceOf(Message::class, $commitCallsLog[0]['params']);
        $this->assertIsArray($commitCallsLog[1]['params']);
    }
}
