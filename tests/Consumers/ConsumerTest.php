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
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Events\MessageConsumed;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\Fakes\FakeConsumer;
use Junges\Kafka\Tests\Fakes\FakeHandler;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\Message;

final class ConsumerTest extends LaravelKafkaTestCase
{
    private ?MessageConsumer $stoppableConsumer = null;
    private bool $stoppableConsumerStopped = false;
    private string $stoppedConsumerMessage = "";
    private int $countBeforeConsuming = 0;
    private int $countAfterConsuming = 0;

    #[Test]
    public function it_consumes_a_message_successfully_and_commit(): void
    {
        $fakeHandler = new FakeHandler();

        $message = new Message();
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

        $consumer = new Consumer($config, new JsonDeserializer());
        $consumer->consume();

        $this->assertInstanceOf(ConsumedMessage::class, $fakeHandler->lastMessage());
    }

    #[Test]
    public function it_can_consume_messages(): void
    {
        Event::fake();

        $message = new Message();
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
            ->withHandler($fakeConsumer = new FakeConsumer())
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
        $message = new Message();
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
            ->withHandler($fakeConsumer = new SimpleQueueableHandler())
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

        $fakeHandler = new FakeHandler();

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

        $message = new Message();
        $message->err = 1;
        $message->topic_name = 'test-topic';

        $this->mockConsumerWithMessageFailingCommit($message);

        $consumer = new Consumer($config, new JsonDeserializer());
        $consumer->consume();
    }

    #[Test]
    public function can_stop_consume(): void
    {
        $message = new Message();
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $message2 = new Message();
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
        $this->assertSame("Consumer stopped.", $this->stoppedConsumerMessage);
    }

    #[Test]
    public function it_accepts_custom_committer(): void
    {
        $fakeHandler = new FakeHandler();

        $message = new Message();
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
            ->willReturn(new VoidCommitter());

        $consumer = new Consumer($config, new JsonDeserializer(), $mockedCommitterFactory);
        $consumer->consume();

        $this->assertInstanceOf(ConsumedMessage::class, $fakeHandler->lastMessage());

        $committer = $this->getPropertyWithReflection('committer', $consumer);
        $this->assertInstanceOf(VoidCommitter::class, $committer);
    }

    #[Test]
    public function can_consume_tombstone_messages(): void
    {
        $fakeHandler = new FakeHandler();

        $message = new Message();
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

        $consumer = new Consumer($config, new JsonDeserializer());
        $consumer->consume();

        $this->assertInstanceOf(ConsumedMessage::class, $fakeHandler->lastMessage());
    }

    #[Test]
    public function it_can_restart_consumer(): void
    {
        $message = new Message();
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $message2 = new Message();
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

        $consumer = new Consumer($config, new JsonDeserializer());
        $consumer->consume();

        //finally only one message should be consumed
        $this->assertEquals(1, $consumer->consumedMessagesCount());
    }

    #[Test]
    public function can_stop_consume_if_max_time_reached()
    {
        $message = new Message();
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $message2 = new Message();
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

        $consumer = new Consumer($config, new JsonDeserializer());
        $consumer->consume();

        //finally only one message should be consumed
        $this->assertEquals(1, $consumer->consumedMessagesCount());
    }

    #[Test]
    public function it_run_callbacks_before_consume(): void
    {
        $fakeHandler = new FakeHandler();

        $message = new Message();
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
            afterConsumingCallbacks:[
                fn () => $this->countAfterConsuming = 1,
                fn () => $this->countAfterConsuming++,
            ]
        );

        $consumer = new Consumer($config, new JsonDeserializer());

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
}
