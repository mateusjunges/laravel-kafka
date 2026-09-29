<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Closure;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Sleep;
use JsonException;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Consumers\MessageHandler;
use Junges\Kafka\Consumers\StopReason;
use Junges\Kafka\Contracts\Consumer as ConsumerContract;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Events\ConsumerStarting;
use Junges\Kafka\Events\ConsumerStopped;
use Junges\Kafka\Events\KafkaErrorOccurred;
use Junges\Kafka\Events\MessageConsumed;
use Junges\Kafka\Events\MessageFailed;
use Junges\Kafka\Events\MessageSentToDLQ;
use Junges\Kafka\Events\MessageSkipped;
use Junges\Kafka\Events\OffsetCommitFailed;
use Junges\Kafka\Events\OffsetsCommitted;
use Junges\Kafka\Events\PartitionsAssigned;
use Junges\Kafka\Events\PartitionsRevoked;
use Junges\Kafka\Events\RetryingMessage;
use Junges\Kafka\Events\StartedConsumingMessage;
use Junges\Kafka\Events\StatisticsReported;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\KafkaConsumerTopic;
use RdKafka\Message;
use RdKafka\TopicPartition;
use ReflectionMethod;
use RuntimeException;

final class ConsumerEventsTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_dispatches_events_when_it_starts_and_stops_consuming(): void
    {
        Event::fake();
        $this->mockConsumerWithMessages($this->makeMessage('ok'));

        $consumer = $this->makeConsumer(maxMessages: 1);
        $consumer->consume();

        Event::assertDispatched(ConsumerStarting::class, fn (ConsumerStarting $event) => $event->consumer === $consumer);
        Event::assertDispatched(ConsumerStopped::class, fn (ConsumerStopped $event) => $event->consumer === $consumer
            && $event->reason === StopReason::MessageLimit
            && $event->exception === null);
    }

    #[Test]
    public function it_dispatches_the_stopped_event_with_the_exception_that_stopped_the_consumer(): void
    {
        Event::fake();
        $this->mockConsumerWithMessages($this->makeMessage('failing'));

        $consumer = $this->makeConsumer();

        try {
            $consumer->consume();

            $this->fail('The consumer should stop when a message fails.');
        } catch (ConsumerException $exception) {
            Event::assertDispatched(ConsumerStopped::class, fn (ConsumerStopped $event) => $event->reason === StopReason::Failed
                && $event->exception === $exception);
        }
    }

    #[Test]
    public function it_dispatches_the_stopped_event_with_the_reason_to_stop(): void
    {
        Event::fake();
        $this->mockConsumerWithMessages($this->makeMessage('ok'), $this->makeMessage('ok', offset: 1));

        $consumer = $this->makeConsumer(handler: fn (ConsumerMessage $message, ConsumerContract $consumer) => $consumer->stopConsuming());
        $consumer->consume();

        $this->assertSame(1, $consumer->consumedMessagesCount());
        Event::assertDispatched(ConsumerStopped::class, fn (ConsumerStopped $event) => $event->reason === StopReason::Requested);
    }

    #[Test]
    public function it_stops_with_the_empty_reason_when_there_are_no_messages_left(): void
    {
        Event::fake();

        $timeout = new Message;
        $timeout->err = RD_KAFKA_RESP_ERR__TIMED_OUT;

        $this->mockConsumerWithMessages($timeout);

        $consumer = new Consumer(new Config(
            broker: 'broker',
            topics: ['test-topic'],
            groupId: 'group',
            handler: new MessageHandler(fn () => null),
            stopAfterLastMessage: true,
        ), new JsonDeserializer);
        $consumer->consume();

        Event::assertDispatched(ConsumerStopped::class, fn (ConsumerStopped $event) => $event->reason === StopReason::Empty);
    }

    #[Test]
    public function it_stops_with_the_restart_reason_when_consumers_are_restarted(): void
    {
        Event::fake();
        $this->mockConsumerWithMessages($this->makeMessage('ok'), $this->makeMessage('ok', offset: 1));

        $consumer = new Consumer(new Config(
            broker: 'broker',
            topics: ['test-topic'],
            groupId: 'group',
            handler: new MessageHandler(function () {
                usleep(100 * 1000);
                $this->artisan('kafka:restart-consumers');
            }),
            maxMessages: 2,
            restartInterval: 100,
        ), new JsonDeserializer);
        $consumer->consume();

        $this->assertSame(1, $consumer->consumedMessagesCount());
        Event::assertDispatched(ConsumerStopped::class, fn (ConsumerStopped $event) => $event->reason === StopReason::Restart);
    }

    #[Test]
    public function message_events_receive_the_consumer(): void
    {
        Event::fake();
        $this->mockConsumerWithMessages($this->makeMessage('ok'));

        $consumer = $this->makeConsumer(maxMessages: 1);
        $consumer->consume();

        Event::assertDispatched(StartedConsumingMessage::class, fn (StartedConsumingMessage $event) => $event->consumer === $consumer
            && $event->message->getBody() === '{"body":"ok"}');
        Event::assertDispatched(MessageConsumed::class, fn (MessageConsumed $event) => $event->consumer === $consumer
            && $event->message->getBody() === ['body' => 'ok']);
    }

    #[Test]
    public function it_dispatches_an_event_before_each_retry_and_when_the_message_fails(): void
    {
        Event::fake();
        Sleep::fake();
        $this->mockConsumerWithMessages($this->makeMessage('failing'));

        $consumer = $this->makeConsumer(maxMessages: 1, retries: 2, skipFailedMessages: true);
        $consumer->consume();

        Event::assertDispatchedTimes(RetryingMessage::class, 2);
        Event::assertDispatched(RetryingMessage::class, fn (RetryingMessage $event) => $event->message->getAttempts() === 1
            && $event->throwable instanceof RuntimeException
            && $event->consumer === $consumer);
        Event::assertDispatched(RetryingMessage::class, fn (RetryingMessage $event) => $event->message->getAttempts() === 2);
        Event::assertDispatchedTimes(MessageFailed::class, 1);
        Event::assertDispatched(MessageFailed::class, fn (MessageFailed $event) => $event->message->getAttempts() === 3
            && $event->throwable instanceof RuntimeException
            && $event->consumer === $consumer);
        Event::assertDispatched(MessageSkipped::class, fn (MessageSkipped $event) => $event->consumer === $consumer);
    }

    #[Test]
    public function it_does_not_dispatch_the_retrying_event_when_the_consumer_is_asked_to_stop(): void
    {
        Event::fake();
        Sleep::fake();
        $this->mockConsumerWithMessages($this->makeMessage('failing'));

        $consumer = $this->makeConsumer(retries: 3, skipFailedMessages: true, handler: function (ConsumerMessage $message, ConsumerContract $consumer) {
            $consumer->stopConsuming();

            throw new RuntimeException('fail');
        });
        $consumer->consume();

        Event::assertNotDispatched(RetryingMessage::class);
        Event::assertDispatchedTimes(MessageFailed::class, 1);
    }

    #[Test]
    public function it_dispatches_the_failed_event_when_the_failed_message_stops_the_consumer(): void
    {
        Event::fake();
        $this->mockConsumerWithMessages($this->makeMessage('failing'));

        $consumer = $this->makeConsumer();

        try {
            $consumer->consume();
        } catch (ConsumerException) {
        }

        Event::assertDispatched(MessageFailed::class, fn (MessageFailed $event) => $event->message->getOffset() === 0);
    }

    #[Test]
    public function it_dispatches_the_failed_event_with_the_raw_message_when_it_cant_be_deserialized(): void
    {
        Event::fake();

        $message = $this->makeMessage('ok');
        $message->payload = 'not json';

        $this->mockConsumerWithMessages($message);

        $consumer = $this->makeConsumer(maxMessages: 1, skipFailedMessages: true);
        $consumer->consume();

        Event::assertDispatched(MessageFailed::class, fn (MessageFailed $event) => $event->message->getBody() === 'not json'
            && $event->throwable instanceof JsonException);
    }

    #[Test]
    public function it_dispatches_the_dlq_event_with_the_consumed_message_and_the_published_one(): void
    {
        Event::fake();
        $this->mockConsumerWithMessages($this->makeMessage('failing', offset: 7));
        $this->mockKafkaProducerForDlq([]);

        $consumer = $this->makeConsumer(maxMessages: 1, dlq: 'test-topic-dlq');
        $consumer->consume();

        Event::assertDispatched(MessageSentToDLQ::class, fn (MessageSentToDLQ $event) => $event->consumer === $consumer
            && $event->topic === 'test-topic-dlq'
            && $event->message->getTopicName() === 'test-topic'
            && $event->message->getOffset() === 7
            && $event->payload === '{"body":"failing"}'
            && $event->key === 'key'
            && $event->headers['kafka_throwable_message'] === 'fail'
            && $event->throwable instanceof RuntimeException
            && $event->getMessageIdentifier() === $event->message->getMessageIdentifier());
    }

    #[Test]
    public function it_dispatches_events_when_partitions_are_assigned_and_revoked(): void
    {
        Event::fake();

        $partitions = [new TopicPartition('test-topic', 0)];
        $consumer = $this->makeConsumer();

        $kafkaConsumer = m::mock(KafkaConsumer::class);
        $kafkaConsumer->shouldReceive('assign')->once()->with($partitions);
        $kafkaConsumer->shouldReceive('assign')->once()->with(null);

        $this->rebalance($consumer, $kafkaConsumer, RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS, $partitions);
        $this->rebalance($consumer, $kafkaConsumer, RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS, $partitions);

        Event::assertDispatched(PartitionsAssigned::class, fn (PartitionsAssigned $event) => $event->consumer === $consumer && $event->partitions === $partitions);
        Event::assertDispatched(PartitionsRevoked::class, fn (PartitionsRevoked $event) => $event->consumer === $consumer && $event->partitions === $partitions);
    }

    #[Test]
    public function it_calls_the_partitions_revoked_callback_before_removing_the_partitions(): void
    {
        $partitions = [new TopicPartition('test-topic', 0)];
        $calls = [];

        $consumer = Builder::create(new ConnectionConfig('default', 'broker'), ['test-topic'])
            ->onPartitionsRevoked(function (array $revoked, ConsumerContract $revokedFrom) use (&$calls, &$consumer, $partitions) {
                $this->assertSame($partitions, $revoked);
                $this->assertSame($consumer, $revokedFrom);
                $calls[] = 'callback';
            })
            ->build();

        $kafkaConsumer = m::mock(KafkaConsumer::class);
        $kafkaConsumer->shouldReceive('assign')->once()->with(null)->andReturnUsing(function () use (&$calls) {
            $calls[] = 'unassign';
        });

        $this->rebalance($consumer, $kafkaConsumer, RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS, $partitions);

        $this->assertSame(['callback', 'unassign'], $calls);
    }

    #[Test]
    public function a_rebalance_callback_replaces_the_default_assignment_and_still_dispatches_the_events(): void
    {
        Event::fake();

        $partitions = [new TopicPartition('test-topic', 0)];
        $received = [];

        $consumer = Builder::create(new ConnectionConfig('default', 'broker'), ['test-topic'])
            ->onRebalance(function (KafkaConsumer $kafka, int $error, ?array $assigned) use (&$received) {
                $received[] = [$error, $assigned];
            })
            ->build();

        $kafkaConsumer = m::mock(KafkaConsumer::class);
        $kafkaConsumer->shouldNotReceive('assign');

        $this->rebalance($consumer, $kafkaConsumer, RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS, $partitions);
        $this->rebalance($consumer, $kafkaConsumer, RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS, $partitions);

        $this->assertSame([[RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS, $partitions], [RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS, $partitions]], $received);
        Event::assertDispatched(PartitionsAssigned::class);
        Event::assertDispatched(PartitionsRevoked::class);
    }

    #[Test]
    public function it_dispatches_statistics_and_calls_the_statistics_callback(): void
    {
        Event::fake();

        $received = null;
        $consumer = Builder::create(new ConnectionConfig('analytics', 'broker'), ['test-topic'])
            ->onStatistics(function (mixed $kafka, string $json) use (&$received) {
                $received = $json;
            })
            ->build();

        $this->invoke($consumer, 'handleStatistics', null, '{"type":"consumer","rxmsgs":10}', 31, $this->configCallback($consumer, 'setStatsCb'));

        $this->assertSame('{"type":"consumer","rxmsgs":10}', $received);
        Event::assertDispatched(StatisticsReported::class, fn (StatisticsReported $event) => $event->statistics === ['type' => 'consumer', 'rxmsgs' => 10]
            && $event->connection === 'analytics'
            && $event->consumer === $consumer);
    }

    #[Test]
    public function it_dispatches_events_for_offset_commits(): void
    {
        Event::fake();

        $partitions = [new TopicPartition('test-topic', 0, 10)];
        $consumer = $this->makeConsumer();

        $this->invoke($consumer, 'handleOffsetCommit', null, RD_KAFKA_RESP_ERR_NO_ERROR, $partitions, null);
        $this->invoke($consumer, 'handleOffsetCommit', null, RD_KAFKA_RESP_ERR__NO_OFFSET, [], null);
        $this->invoke($consumer, 'handleOffsetCommit', null, RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT, $partitions, null);

        Event::assertDispatchedTimes(OffsetsCommitted::class, 1);
        Event::assertDispatched(OffsetsCommitted::class, fn (OffsetsCommitted $event) => $event->consumer === $consumer && $event->partitions === $partitions);
        Event::assertDispatchedTimes(OffsetCommitFailed::class, 1);
        Event::assertDispatched(OffsetCommitFailed::class, fn (OffsetCommitFailed $event) => $event->errorCode === RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT
            && $event->error === rd_kafka_err2str(RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT)
            && $event->partitions === $partitions);
    }

    #[Test]
    public function it_dispatches_errors_reported_by_librdkafka(): void
    {
        Event::fake();

        $consumer = $this->makeConsumer();

        $this->invoke($consumer, 'handleError', null, RD_KAFKA_RESP_ERR__ALL_BROKERS_DOWN, 'All brokers are down', null);

        Event::assertDispatched(KafkaErrorOccurred::class, fn (KafkaErrorOccurred $event) => $event->errorCode === RD_KAFKA_RESP_ERR__ALL_BROKERS_DOWN
            && $event->error === 'All brokers are down'
            && $event->connection === 'default'
            && $event->consumer === $consumer);
    }

    #[Test]
    public function it_only_sets_the_error_callback_when_there_is_a_callback_or_a_listener(): void
    {
        $this->assertArrayNotHasKey('error_cb', $this->makeConf($this->makeConsumer())->dump());

        $withCallback = Builder::create(new ConnectionConfig('default', 'broker'), ['test-topic'])->onError(fn () => null)->build();
        $this->assertArrayHasKey('error_cb', $this->makeConf($withCallback)->dump());

        Event::listen(KafkaErrorOccurred::class, fn () => null);
        $this->assertArrayHasKey('error_cb', $this->makeConf($this->makeConsumer())->dump());
    }

    private function makeConsumer(
        int $maxMessages = -1,
        int $retries = 0,
        bool $skipFailedMessages = false,
        ?string $dlq = null,
        ?Closure $handler = null,
    ): Consumer {
        $handler ??= function (ConsumerMessage $message): void {
            if ($message->getBody()['body'] === 'failing') {
                throw new RuntimeException('fail');
            }
        };

        return new Consumer(new Config(
            broker: 'broker',
            topics: ['test-topic'],
            groupId: 'group',
            handler: new MessageHandler($handler),
            dlq: $dlq,
            maxMessages: $maxMessages,
            skipFailedMessages: $skipFailedMessages,
            failedMessageRetries: $retries,
        ), new JsonDeserializer);
    }

    private function mockConsumerWithMessages(Message ...$messages): void
    {
        $mockedKafkaConsumer = $this->mockKafkaConsumer();
        $mockedKafkaConsumer->shouldReceive('subscribe');
        $mockedKafkaConsumer->shouldReceive('getAssignment')->andReturn([new TopicPartition('test-topic', 0)]);
        $mockedKafkaConsumer->shouldReceive('consume')->andReturnUsing(function () use (&$messages) {
            return array_shift($messages) ?? $this->makeMessage('ok', offset: 99);
        });
        $mockedKafkaConsumer->shouldReceive('newTopic')->andReturn(m::mock(KafkaConsumerTopic::class, ['offsetStore' => null]));

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
    }

    private function makeMessage(string $payload, int $offset = 0): Message
    {
        $message = new Message;
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = json_encode(['body' => $payload]);
        $message->offset = $offset;
        $message->partition = 0;
        $message->headers = [];

        return $message;
    }

    private function rebalance(Consumer $consumer, KafkaConsumer $kafkaConsumer, int $error, array $partitions): void
    {
        $this->invoke($consumer, 'rebalance', $kafkaConsumer, $error, $partitions, $this->configCallback($consumer, 'setRebalanceCb'));
    }

    private function configCallback(Consumer $consumer, string $method): ?callable
    {
        return $this->getPropertyWithReflection('config', $consumer)->getConfigCallbacks()[$method] ?? null;
    }

    private function makeConf(Consumer $consumer): Conf
    {
        return $this->invoke($consumer, 'makeConf');
    }

    private function invoke(Consumer $consumer, string $method, mixed ...$arguments): mixed
    {
        return (new ReflectionMethod($consumer, $method))->invoke($consumer, ...$arguments);
    }
}
