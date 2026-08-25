<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Closure;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use WeakReference;

final class ConsumerSignalHandlersTest extends LaravelKafkaTestCase
{
    /** @var array<int, callable|int> */
    private array $originalHandlers = [];

    private bool $originalAsyncSignals = false;

    /** @var list<int> */
    private array $signalsReceivedByHostHandler = [];

    private Closure $hostHandler;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pcntl') || ! function_exists('posix_kill')) {
            $this->markTestSkipped('The pcntl and posix extensions are required to test signal handling.');
        }

        $this->originalAsyncSignals = pcntl_async_signals();
        $this->hostHandler = function (int $signal): void {
            $this->signalsReceivedByHostHandler[] = $signal;
        };

        foreach ($this->handledSignals() as $signal) {
            $this->originalHandlers[$signal] = pcntl_signal_get_handler($signal);
            pcntl_signal($signal, $this->hostHandler);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalHandlers as $signal => $handler) {
            pcntl_signal($signal, $handler);
        }

        if ($this->originalHandlers !== []) {
            pcntl_async_signals($this->originalAsyncSignals);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_invokes_the_handler_registered_by_the_host_process_when_a_signal_arrives_while_consuming(): void
    {
        $this->mockConsumerRaisingSignalWhileConsuming(SIGTERM);
        $this->mockProducer();

        $this->buildConsumer()->consume();

        $this->assertSame([SIGTERM], $this->signalsReceivedByHostHandler);
    }

    #[Test]
    public function it_still_stops_consuming_when_a_signal_arrives(): void
    {
        $this->mockConsumerRaisingSignalWhileConsuming(SIGTERM, $this->message());
        $this->mockProducer();

        $consumer = $this->buildConsumer();
        $consumer->consume();

        $this->assertSame(1, $consumer->consumedMessagesCount());
    }

    #[Test]
    public function it_restores_the_signal_handlers_of_the_host_process_after_consuming(): void
    {
        $this->mockConsumerRaisingSignalWhileConsuming(SIGTERM);
        $this->mockProducer();

        $this->buildConsumer()->consume();

        foreach ($this->handledSignals() as $signal) {
            $this->assertSame($this->hostHandler, pcntl_signal_get_handler($signal));
        }
    }

    #[Test]
    public function it_restores_the_signal_handlers_of_the_host_process_when_consuming_fails(): void
    {
        $failure = new Message;
        $failure->err = 1;
        $failure->topic_name = 'test';

        $this->mockConsumerWithMessageFailingCommit($failure);
        $this->mockProducer();

        try {
            $this->buildConsumer()->consume();
            $this->fail('A consumer exception was expected.');
        } catch (ConsumerException) {
        }

        foreach ($this->handledSignals() as $signal) {
            $this->assertSame($this->hostHandler, pcntl_signal_get_handler($signal));
        }
    }

    #[Test]
    public function it_restores_the_default_signal_disposition_when_the_host_process_had_none(): void
    {
        pcntl_signal(SIGTERM, SIG_DFL);

        $this->mockConsumerRaisingSignalWhileConsuming(SIGTERM);
        $this->mockProducer();

        $this->buildConsumer()->consume();

        $this->assertSame(SIG_DFL, pcntl_signal_get_handler(SIGTERM));
        $this->assertSame([], $this->signalsReceivedByHostHandler);
    }

    #[Test]
    public function it_restores_the_async_signals_setting_of_the_host_process_after_consuming(): void
    {
        pcntl_async_signals(false);

        $this->mockConsumerRaisingSignalWhileConsuming(SIGTERM);
        $this->mockProducer();

        $this->buildConsumer()->consume();

        $this->assertFalse(pcntl_async_signals());
    }

    #[Test]
    public function it_does_not_keep_the_consumer_alive_through_its_signal_handlers_after_consuming(): void
    {
        $this->mockConsumerRaisingSignalWhileConsuming(SIGTERM);
        $this->mockProducer();

        $consumer = $this->buildConsumer();
        $reference = WeakReference::create($consumer);

        $consumer->consume();
        unset($consumer);
        gc_collect_cycles();

        $this->assertTrue($reference->get() === null, 'The consumer is still referenced after consume() and its last strong reference was dropped.');
    }

    /** @return list<int> */
    private function handledSignals(): array
    {
        return [SIGTERM, SIGQUIT, SIGINT];
    }

    private function buildConsumer(): MessageConsumer
    {
        return Kafka::consumer(['test'])
            ->withHandler(static function (): void {})
            ->stopAfterLastMessage()
            ->withMaxTime(5)
            ->build();
    }

    private function mockConsumerRaisingSignalWhileConsuming(int $signal, ?Message $message = null): void
    {
        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturnUsing(function () use ($signal, $message): Message {
                posix_kill(posix_getpid(), $signal);

                return $message ?? $this->timedOutMessage();
            })
            ->shouldReceive('commit')
            ->andReturn()
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
    }

    private function timedOutMessage(): Message
    {
        $message = new Message;
        $message->err = RD_KAFKA_RESP_ERR__TIMED_OUT;

        return $message;
    }

    private function message(): Message
    {
        $message = new Message;
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $message->key = 'key';
        $message->topic_name = 'test';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        return $message;
    }
}
