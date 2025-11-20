<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Junges\Kafka\Commit\VoidCommitter;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Consumers\CallableConsumer;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Consumers\DispatchQueuedHandler;
use Junges\Kafka\Contracts\CommitterFactory;
use Junges\Kafka\Contracts\Consumer as ContractsConsumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Events\MessageConsumed;
use Junges\Kafka\Events\MessageSentToDLQ;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Exceptions\ContextAwareException;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\Fakes\FakeConsumer;
use Junges\Kafka\Tests\Fakes\FakeHandler;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\TopicPartition;
use RuntimeException;

final class ConsumerTest extends LaravelKafkaTestCase
{
    private ?MessageConsumer $stoppableConsumer = null;

    private bool $stoppableConsumerStopped = false;

    private string $stoppedConsumerMessage = '';

    private int $countBeforeConsuming = 0;

    private int $countAfterConsuming = 0;

    #[Test]
    public function it_consumes_a_message_successfully_and_commit(): void
    {
        $fakeHandler = new FakeHandler;

        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $this->mockConsumerWithMessage($message);

        $this->mockProducer();

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            sasl: null,
            dlq: null,
            maxMessages: 1,
            maxCommitRetries: 1
        );

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();

        $this->assertInstanceOf(ConsumedMessage::class, $fakeHandler->lastMessage());
    }

    #[Test]
    public function it_can_consume_messages(): void
    {
        Event::fake();

        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test';
        $message->payload = '{"body": "message payload"}';
        $message->headers = [];
        $message->partition = 1;
        $message->offset = 0;

        $this->mockConsumerWithMessage($message);

        $this->mockProducer();

        $consumer = Kafka::consumer(['test'])
            ->withHandler($fakeConsumer = new FakeConsumer)
            ->withAutoCommit()
            ->withMaxMessages(1)
            ->build();

        $consumer->consume();
        $this->assertInstanceOf(ConsumedMessage::class, $fakeConsumer->getMessage());
        Event::assertDispatched(MessageConsumed::class, fn (MessageConsumed $e) => $e->message === $fakeConsumer->getMessage());
    }

    #[Test]
    public function it_can_consume_messages_with_queueable_handlers(): void
    {
        Bus::fake();
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test';
        $message->payload = '{"body": "message payload"}';
        $message->headers = [];
        $message->partition = 1;
        $message->offset = 0;

        $this->mockConsumerWithMessage($message);

        $this->mockProducer();

        $consumer = Kafka::consumer(['test'])
            ->withHandler($fakeConsumer = new SimpleQueueableHandler)
            ->withAutoCommit()
            ->withMaxMessages(1)
            ->build();

        $consumer->consume();

        Bus::assertDispatched(DispatchQueuedHandler::class);
    }

    #[Test]
    public function consume_message_with_error(): void
    {
        $this->mockProducer();

        $this->expectException(ConsumerException::class);

        $fakeHandler = new FakeHandler;

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            sasl: null,
            dlq: null,
            maxMessages: 1,
            maxCommitRetries: 1
        );

        $message = new Message;
        $message->err = 1;
        $message->topic_name = 'test-topic';

        $this->mockConsumerWithMessageFailingCommit($message);

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();
    }

    #[Test]
    public function can_stop_consume(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $message2 = new Message;
        $message2->err = 0;
        $message2->key = 'key2';
        $message2->topic_name = 'test2';
        $message2->payload = '{"body": "message payload2"}';
        $message2->offset = 0;
        $message2->partition = 1;
        $message2->headers = [];

        $this->mockConsumerWithMessage($message, $message2);

        $this->mockProducer();

        $this->stoppableConsumer = Kafka::consumer(['test'])
            ->onStopConsuming(function () {
                $this->stoppableConsumerStopped = true;
                $this->stoppedConsumerMessage = 'Consumer stopped.';
            })
            ->withHandler(function (ConsumerMessage $message, MessageConsumer $consumer) {
                if ($message->getKey() === 'key2') {
                    $consumer->stopConsuming();
                }
            })
            ->withAutoCommit()
            ->build();

        $this->stoppableConsumer->consume();

        $this->assertSame(2, $this->stoppableConsumer->consumedMessagesCount());
        $this->assertTrue($this->stoppableConsumerStopped);
        $this->assertSame('Consumer stopped.', $this->stoppedConsumerMessage);
    }

    #[Test]
    public function it_accepts_custom_committer(): void
    {
        $fakeHandler = new FakeHandler;

        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $this->mockConsumerWithMessage($message);

        $this->mockProducer();

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            sasl: null,
            dlq: null,
            maxMessages: 1,
            maxCommitRetries: 1
        );

        $mockedCommitterFactory = $this->createMock(CommitterFactory::class);
        $mockedCommitterFactory->expects($this->once())
            ->method('make')
            ->willReturn(new VoidCommitter);

        $consumer = new Consumer($config, new JsonDeserializer, $mockedCommitterFactory);
        $consumer->consume();

        $this->assertInstanceOf(ConsumedMessage::class, $fakeHandler->lastMessage());

        $committer = $this->getPropertyWithReflection('committer', $consumer);
        $this->assertInstanceOf(VoidCommitter::class, $committer);
    }

    #[Test]
    public function can_consume_tombstone_messages(): void
    {
        $fakeHandler = new FakeHandler;

        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = null;
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $this->mockConsumerWithMessage($message);

        $this->mockProducer();

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            sasl: null,
            dlq: null,
            maxMessages: 1,
            maxCommitRetries: 1
        );

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();

        $this->assertInstanceOf(ConsumedMessage::class, $fakeHandler->lastMessage());
    }

    #[Test]
    public function it_can_restart_consumer(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $message2 = new Message;
        $message2->err = 0;
        $message2->key = 'key2';
        $message2->topic_name = 'test';
        $message2->payload = '{"body": "message payload2"}';
        $message2->offset = 0;
        $message2->partition = 1;
        $message2->headers = [];

        $this->mockConsumerWithMessage($message, $message2);
        $this->mockProducer();

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message) {
                // sleep 100 milliseconds to simulate restart interval check
                usleep(100 * 1000);
                $this->artisan('kafka:restart-consumers');
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
            sasl: null,
            dlq: null,
            maxMessages: 2,
            maxCommitRetries: 1,
            restartInterval : 100
        );

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();

        // finally only one message should be consumed
        $this->assertEquals(1, $consumer->consumedMessagesCount());
    }

    #[Test]
    public function can_stop_consume_if_max_time_reached()
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $message2 = new Message;
        $message2->err = 0;
        $message2->key = 'key2';
        $message2->topic_name = 'test2';
        $message2->payload = '{"body": "message payload2"}';
        $message2->offset = 0;
        $message2->partition = 1;
        $message2->headers = [];

        $this->mockConsumerWithMessage($message, $message2);
        $this->mockProducer();

        $fakeHandler = new CallableConsumer(
            function (ConsumerMessage $message) {
                sleep(2);
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
            sasl: null,
            dlq: null,
            maxMessages: 2,
            maxTime: 1,
        );

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();

        // finally only one message should be consumed
        $this->assertEquals(1, $consumer->consumedMessagesCount());
    }

    #[Test]
    public function it_run_callbacks_before_consume(): void
    {
        $fakeHandler = new FakeHandler;

        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $this->mockConsumerWithMessage($message);

        $this->mockProducer();

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            sasl: null,
            dlq: null,
            maxMessages: 1,
            maxCommitRetries: 1,
            beforeConsumingCallbacks: [
                fn () => $this->countBeforeConsuming = 1,
                fn () => $this->countBeforeConsuming++,
            ],
            afterConsumingCallbacks: [
                fn () => $this->countAfterConsuming = 1,
                fn () => $this->countAfterConsuming++,
            ]
        );

        $consumer = new Consumer($config, new JsonDeserializer);

        $consumer->consume();

        $this->assertInstanceOf(ConsumedMessage::class, $fakeHandler->lastMessage());
        $this->assertSame(2, $this->countBeforeConsuming);
        $this->assertSame(2, $this->countAfterConsuming);
    }

    #[Test]
    public function it_can_test_macroed_consumers(): void
    {
        $array = ['key' => false];
        Kafka::macro('macroedConsumer', function (string $topic) use (&$array) {
            return $this->consumer([$topic])->withHandler(function () use (&$array) {
                $array['key'] = true;
            });
        });

        Kafka::fake();
        Kafka::shouldReceiveMessages([
            new ConsumedMessage(
                topicName: 'change-key-to-true',
                partition: 0,
                headers: [],
                body: ['post_id' => 1],
                key: null,
                offset: 0,
                timestamp: 0
            ),
        ]);

        /** @var MessageConsumer $consumer */
        $consumer = Kafka::macroedConsumer('change-key-to-true')->build();
        $consumer->consume();

        $this->assertTrue($array['key']);
    }

    #[Test]
    public function it_provides_consumer_to_handler_classes(): void
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
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $handlerCalled = false;
        $consumerProvided = false;
        $messageData = null;

        $handler = new class($handlerCalled, $consumerProvided, $messageData) implements Handler
        {
            public function __construct(
                private bool &$handlerCalledRef,
                private bool &$consumerProvidedRef,
                private mixed &$messageDataRef
            ) {}

            public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
            {
                $this->handlerCalledRef = true;
                $this->consumerProvidedRef = $consumer !== null;
                $this->messageDataRef = [
                    'topic' => $message->getTopicName(),
                    'partition' => $message->getPartition(),
                    'offset' => $message->getOffset(),
                    'has_consumer' => $consumer !== null,
                    'can_commit' => method_exists($consumer, 'commit'),
                ];
            }
        };

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: new CallableConsumer($handler, []),
            maxMessages: 1,
            autoCommit: false
        );

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();

        $this->assertTrue($handlerCalled);
        $this->assertTrue($consumerProvided);
        $this->assertEquals('test-topic', $messageData['topic']);
        $this->assertEquals(1, $messageData['partition']);
        $this->assertEquals(5, $messageData['offset']);
        $this->assertTrue($messageData['has_consumer']);
        $this->assertTrue($messageData['can_commit']);
    }

    #[Test]
    public function it_allows_handler_classes_to_commit_manually(): void
    {
        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 3;
        $message->partition = 2;
        $message->headers = [];

        $commitCalled = false;
        $commitParams = null;

        // Mock the committer to track manual commits
        $customCommitter = new class($commitCalled, $commitParams) implements \Junges\Kafka\Contracts\Committer
        {
            public function __construct(private bool &$commitCalledRef, private mixed &$commitParamsRef) {}

            public function commitMessage(Message $message, bool $success): void
            {
                // Not called for manual commits
            }

            public function commitDlq(Message $message): void
            {
                // Not relevant for this test
            }

            public function commit(mixed $messageOrOffsets = null): void
            {
                $this->commitCalledRef = true;
                $this->commitParamsRef = $messageOrOffsets;
            }

            public function commitAsync(mixed $messageOrOffsets = null): void
            {
                // Not used in this test
            }
        };

        $customCommitterFactory = new class($customCommitter) implements CommitterFactory
        {
            public function __construct(private \Junges\Kafka\Contracts\Committer $committer) {}

            public function make(KafkaConsumer $kafkaConsumer, Config $config): \Junges\Kafka\Contracts\Committer
            {
                return $this->committer;
            }
        };

        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
        $this->mockProducer();

        $handlerCalled = false;

        $handler = new class($handlerCalled) implements Handler
        {
            public function __construct(private bool &$handlerCalledRef) {}

            public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
            {
                $this->handlerCalledRef = true;
                // Manually commit using the consumer parameter - just like closures!
                $consumer->commit($message);
            }
        };

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: new CallableConsumer($handler, []),
            maxMessages: 1,
            autoCommit: false
        );

        $consumer = new Consumer($config, new JsonDeserializer, $customCommitterFactory);
        $consumer->consume();

        $this->assertTrue($handlerCalled);
        $this->assertTrue($commitCalled);
        $this->assertInstanceOf(ConsumerMessage::class, $commitParams);
        $this->assertEquals('test-topic', $commitParams->getTopicName());
        $this->assertEquals(2, $commitParams->getPartition());
        $this->assertEquals(3, $commitParams->getOffset());
    }

    #[Test]
    public function it_returns_empty_array_when_consumer_not_initialized(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: new FakeHandler,
            sasl: null,
            dlq: null,
            maxMessages: 1,
            maxCommitRetries: 1
        );

        $consumer = new Consumer($config, new JsonDeserializer);

        $partitions = $consumer->getAssignedPartitions();

        $this->assertIsArray($partitions);
        $this->assertEmpty($partitions);
    }

    #[Test]
    public function it_returns_assigned_partitions_when_consumer_initialized(): void
    {
        $fakeHandler = new FakeHandler;

        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $expectedPartitions = [
            new TopicPartition('test-topic', 0),
            new TopicPartition('test-topic', 1),
        ];

        $this->mockConsumerWithMessageAndPartitions($message, $expectedPartitions);
        $this->mockProducer();

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            sasl: null,
            dlq: null,
            maxMessages: 1,
            maxCommitRetries: 1
        );

        $consumer = new Consumer($config, new JsonDeserializer);

        $consumer->consume();

        $partitions = $consumer->getAssignedPartitions();

        $this->assertIsArray($partitions);
        $this->assertCount(2, $partitions);
        $this->assertInstanceOf(TopicPartition::class, $partitions[0]);
        $this->assertInstanceOf(TopicPartition::class, $partitions[1]);
    }

    #[Test]
    public function it_sends_message_to_dlq_on_handler_exception(): void
    {
        Event::fake();

        $fakeHandler = new class extends ContractsConsumer
        {
            public function handle(ConsumerMessage $message, MessageConsumer $consumer): void
            {
                throw new RuntimeException('fail');
            }
        };

        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $expectedHeaders = [
            'kafka_throwable_message' => 'fail',
            'kafka_throwable_code' => 0,
            'kafka_throwable_class_name' => RuntimeException::class,
        ];

        $this->mockConsumerWithMessage($message);
        $this->mockKafkaProducerForDlq($expectedHeaders);

        $config = new Config(
            broker: 'localhost:9092',
            topics: ['test-topic'],
            securityProtocol: null,
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            sasl: null,
            dlq: 'dlq-topic',
            maxMessages: 1,
        );

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();

        Event::assertDispatched(MessageSentToDLQ::class, function (MessageSentToDLQ $e) {
            return $e->throwable instanceof RuntimeException;
        });
    }

    #[Test]
    public function it_sends_message_to_dlq_on_handler_exception_append_context(): void
    {
        Event::fake();

        $context = ['context_key' => 'context_value'];

        $fakeHandler = new class($context) extends ContractsConsumer
        {
            public function __construct(private array $context) {}

            public function handle(ConsumerMessage $message, MessageConsumer $consumer): void
            {
                throw new ContextAwareException($this->context, 'fail');
            }
        };

        $message = new Message;
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $expectedHeaders = [
            'kafka_throwable_message' => 'fail',
            'kafka_throwable_code' => 0,
            'kafka_throwable_class_name' => ContextAwareException::class,
            'context_key' => 'context_value',
        ];

        $this->mockConsumerWithMessage($message);
        $this->mockKafkaProducerForDlq($expectedHeaders);

        $config = new Config(
            broker: 'localhost:9092',
            topics: ['test-topic'],
            securityProtocol: null,
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            sasl: null,
            dlq: 'dlq-topic',
            maxMessages: 1,
        );

        $consumer = new Consumer($config, new JsonDeserializer);
        $consumer->consume();

        Event::assertDispatched(MessageSentToDLQ::class, function (MessageSentToDLQ $e) {
            return $e->throwable instanceof ContextAwareException;
        });
    }

    private function mockConsumerWithMessageAndPartitions(Message $message, array $partitions): void
    {
        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->andReturn()
            ->shouldReceive('getAssignment')
            ->andReturn($partitions)
            ->getMock();

        $this->app->bind(KafkaConsumer::class, function () use ($mockedKafkaConsumer) {
            return $mockedKafkaConsumer;
        });
    }
}
