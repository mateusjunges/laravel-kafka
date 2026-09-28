<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Consumers\Builder as ConsumerBuilder;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Events\CouldNotPublishMessage as CouldNotPublishMessageEvent;
use Junges\Kafka\Events\MessagePublished;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\PendingMessage;
use Junges\Kafka\Tests\Fakes\FakeDeserializer;
use Junges\Kafka\Tests\Fakes\FakeSerializer;
use LogicException;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\Conf;
use RdKafka\Producer;
use RdKafka\ProducerTopic;

final class KafkaTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_can_publish_messages_to_kafka(): void
    {
        Event::fake();

        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->andReturn(m::self())
            ->getMock();

        $this->mockRdKafkaProducer($mockedProducerTopic);

        Kafka::publish('test')
            ->withKey(Str::uuid()->toString())
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->send();

        Event::assertDispatched(MessagePublished::class);
    }

    #[Test]
    public function it_can_publish_messages_synchronously(): void
    {
        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->twice()
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')->with('test')->once()->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')->twice()
            ->shouldReceive('flush')->twice()->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(Producer::class, fn () => $mockedProducer);

        Kafka::publishSync('test')->withBodyKey('test', ['test'])->send();
        Kafka::publishSync('test')->withBodyKey('test', ['test'])->send();
    }

    #[Test]
    public function it_publishes_messages_asynchronously_using_a_single_producer_per_connection(): void
    {
        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->twice()
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')->with('test')->once()->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')->twice()
            ->shouldReceive('flush')->once()->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $producersCreated = 0;

        $this->app->bind(Producer::class, function () use ($mockedProducer, &$producersCreated) {
            $producersCreated++;

            return $mockedProducer;
        });

        Kafka::publish('test')->withBodyKey('test', ['test'])->send();
        Kafka::publish('test')->withBodyKey('test', ['test'])->send();

        $this->assertSame(1, $producersCreated);

        Kafka::flush();
    }

    #[Test]
    public function it_flushes_queued_messages_when_the_application_terminates(): void
    {
        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')
            ->shouldReceive('flush')->once()->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(Producer::class, fn () => $mockedProducer);

        Kafka::publish('test')->withBody('foo')->send();

        $this->app->terminate();
    }

    #[Test]
    public function it_flushes_queued_messages_after_each_queued_job(): void
    {
        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')
            ->shouldReceive('flush')->once()->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(Producer::class, fn () => $mockedProducer);

        Kafka::publish('test')->withBody('foo')->send();

        event(new JobProcessed('redis', m::mock(Job::class)));
    }

    #[Test]
    public function it_reports_flush_failures_instead_of_throwing_them_when_the_application_terminates(): void
    {
        $handler = m::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->once()->with(m::type(CouldNotPublishMessage::class));
        $this->app->instance(ExceptionHandler::class, $handler);

        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')
            ->shouldReceive('flush')->andReturn(RD_KAFKA_RESP_ERR__FAIL)
            ->getMock();

        $this->app->bind(Producer::class, fn () => $mockedProducer);

        config(['kafka.connections.default.producer.flush_retries' => 1]);

        Kafka::publish('test')->withBody('foo')->send();

        $this->app->terminate();
    }

    #[Test]
    public function it_publishes_using_the_given_connection(): void
    {
        config(['kafka.connections.analytics' => [
            'brokers' => 'analytics:9092',
            'options' => ['client.id' => 'analytics-client'],
            'producer' => ['options' => ['batch.num.messages' => 500, 'enable.idempotence' => true]],
            'consumer' => ['options' => ['session.timeout.ms' => 10000]],
        ]]);

        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->andReturn(m::self())
            ->getMock();

        $conf = $this->mockRdKafkaProducer($mockedProducerTopic);

        Kafka::connection('analytics')->publishSync('test')->withBody('foo')->send();

        $options = $conf()->dump();

        $this->assertSame('analytics:9092', $options['metadata.broker.list']);
        $this->assertSame('analytics-client', $options['client.id']);
        $this->assertSame('500', $options['batch.num.messages']);
        $this->assertSame('true', $options['enable.idempotence']);
        $this->assertNotSame('10000', $options['session.timeout.ms'] ?? null);
    }

    #[Test]
    public function it_applies_the_connection_callbacks_to_the_producer(): void
    {
        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->andReturn(m::self())
            ->getMock();

        $conf = $this->mockRdKafkaProducer($mockedProducerTopic);

        Kafka::connection()->onError($callback = function () {});

        Kafka::publishSync('test')->withBody('foo')->send();

        $this->assertSame(['setErrorCb' => $callback], Kafka::connection()->getConfig()->callbacks);
        $this->assertInstanceOf(Conf::class, $conf());
    }

    #[Test]
    public function it_registers_delivery_report_callbacks_on_connections(): void
    {
        Kafka::connection()->onDeliveryReport($callback = fn () => null);

        $this->assertSame($callback, Kafka::connection()->getConfig()->callbacks['setDrMsgCb']);
    }

    #[Test]
    public function it_does_not_register_connection_callbacks_after_the_producer_is_created(): void
    {
        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->andReturn(m::self())
            ->getMock();

        $this->mockRdKafkaProducer($mockedProducerTopic);

        Kafka::connection()->onLog(fn () => null);

        Kafka::publishSync('test')->withBody('foo')->send();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Configuration callbacks must be registered on the [default] Kafka connection before its producer is created');

        Kafka::connection()->onError(fn () => null);
    }

    #[Test]
    public function the_kafka_fake_does_not_register_connection_callbacks_after_publishing_either(): void
    {
        Kafka::fake();

        Kafka::publish('test')->withBody('foo')->send();

        $this->expectException(LogicException::class);

        Kafka::connection()->onOAuthBearerTokenRefresh(fn () => null);
    }

    #[Test]
    public function it_uses_the_serializer_and_deserializer_of_the_connection(): void
    {
        config(['kafka.connections.avro' => [
            'brokers' => 'avro:9092',
            'producer' => ['serializer' => FakeSerializer::class],
            'consumer' => ['deserializer' => FakeDeserializer::class],
        ]]);

        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->withArgs(fn ($partition, $flags, $payload) => $payload === 'serialized by '.FakeSerializer::class)
            ->andReturn(m::self())
            ->getMock();

        $this->mockRdKafkaProducer($mockedProducerTopic);

        Kafka::connection('avro')->publishSync('test')->withBody(['foo' => 'bar'])->send();

        $this->assertInstanceOf(FakeDeserializer::class, $this->getPropertyWithReflection('deserializer', Kafka::connection('avro')->consumer()));
        $this->assertInstanceOf(JsonDeserializer::class, $this->getPropertyWithReflection('deserializer', Kafka::consumer()));
    }

    #[Test]
    public function the_producer_authenticates_with_the_sasl_credentials_of_the_connection(): void
    {
        config(['kafka.connections.default' => [
            'brokers' => 'broker',
            'sasl' => ['username' => 'user', 'password' => 'secret', 'mechanism' => 'SCRAM-SHA-512'],
        ]]);

        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->andReturn(m::self())
            ->getMock();

        $conf = $this->mockRdKafkaProducer($mockedProducerTopic);

        Kafka::publishSync('test')->withBody('foo')->send();

        $options = $conf()->dump();

        $this->assertSame('sasl_plaintext', mb_strtolower($options['security.protocol']));
        $this->assertSame('user', $options['sasl.username']);
    }

    #[Test]
    public function it_throws_an_exception_when_the_connection_is_not_configured(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Kafka connection [missing] is not configured.');

        Kafka::connection('missing');
    }

    #[Test]
    public function it_uses_the_configured_default_connection(): void
    {
        config([
            'kafka.default' => 'analytics',
            'kafka.connections.analytics' => ['brokers' => 'analytics:9092'],
        ]);

        $this->assertSame('analytics', Kafka::connection()->getName());
        $this->assertSame('analytics:9092', Kafka::connection()->getConfig()->brokers);
    }

    #[Test]
    public function it_can_not_publish_a_message_without_a_topic(): void
    {
        $this->expectException(LogicException::class);

        Kafka::publish()->withBody('foo')->send();
    }

    #[Test]
    public function i_can_switch_serializers_on_the_fly(): void
    {
        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->withArgs(fn ($partition, $flags, $payload) => $payload === 'serialized')
            ->andReturn(m::self())
            ->getMock();

        $this->mockRdKafkaProducer($mockedProducerTopic);

        $serializer = m::mock(MessageSerializer::class)
            ->shouldReceive('serialize')->once()
            ->andReturnUsing(fn (ProducerMessage $message) => $message->withBody('serialized'))
            ->getMock();

        Kafka::publishSync('test-topic')
            ->usingSerializer($serializer)
            ->withBodyKey('test', ['test'])
            ->send();
    }

    #[Test]
    public function it_does_not_send_messages_to_kafka_if_using_fake(): void
    {
        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')->never()
            ->shouldReceive('producev')->never()
            ->shouldReceive('poll')->never()
            ->shouldReceive('flush')->never()
            ->getMock();

        $this->app->bind(Producer::class, fn () => $mockedProducer);

        Kafka::fake();

        Kafka::publish('test-topic')
            ->withKey(Str::uuid()->toString())
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->send();

        Kafka::assertPublishedOn('test-topic');
    }

    #[Test]
    public function i_can_set_the_entire_message_with_message_object(): void
    {
        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->times(2)
            ->andReturn(m::self())
            ->getMock();

        $this->mockRdKafkaProducer($mockedProducerTopic);

        $message = Message::create()
            ->withHeaders(['foo' => 'bar'])
            ->onTopic('test')
            ->withKey('message-key')
            ->withBody(['foo' => 'bar']);

        Kafka::publish()->withMessage($message)->send();

        Kafka::publish('test')
            ->withMessage(new Message(
                headers: ['foo' => 'bar'],
                body: ['foo' => 'bar'],
                key: 'message-key',
            ))
            ->send();
    }

    #[Test]
    public function create_consumer_returns_a_consumer_builder_instance(): void
    {
        $consumer = Kafka::consumer();

        $this->assertInstanceOf(ConsumerBuilder::class, $consumer);
    }

    #[Test]
    public function create_consumer_default_configs(): void
    {
        $consumer = Kafka::consumer();

        $this->assertInstanceOf(ConsumerBuilder::class, $consumer);
        $this->assertEquals('group', $this->getPropertyWithReflection('groupId', $consumer));
        $this->assertEquals('localhost:9092', $this->getPropertyWithReflection('brokers', $consumer));
        $this->assertEquals([], $this->getPropertyWithReflection('topics', $consumer));
    }

    #[Test]
    public function it_creates_consumers_using_the_connection_configuration(): void
    {
        config(['kafka.connections.analytics' => [
            'brokers' => 'analytics:9092',
            'security_protocol' => 'SASL_SSL',
            'sasl' => ['username' => 'user', 'password' => 'secret', 'mechanism' => 'SCRAM-SHA-512'],
            'options' => ['client.id' => 'analytics-client'],
            'consumer' => [
                'group_id' => 'analytics-group',
                'auto_commit' => false,
                'timeout_ms' => 500,
                'options' => ['auto.offset.reset' => 'earliest'],
            ],
        ]]);

        $consumer = Kafka::connection('analytics')->consumer(['topic']);

        $this->assertSame('analytics:9092', $this->getPropertyWithReflection('brokers', $consumer));
        $this->assertSame('analytics-group', $this->getPropertyWithReflection('groupId', $consumer));
        $this->assertFalse($this->getPropertyWithReflection('autoCommit', $consumer));
        $this->assertSame(500, $this->getPropertyWithReflection('consumerTimeoutInMs', $consumer));
        $this->assertSame(
            ['client.id' => 'analytics-client', 'auto.offset.reset' => 'earliest'],
            $this->getPropertyWithReflection('options', $consumer)
        );
        $this->assertEquals(
            new Sasl('user', 'secret', 'SCRAM-SHA-512'),
            $this->getPropertyWithReflection('saslConfig', $consumer)
        );

        $this->assertSame('other-group', $this->getPropertyWithReflection('groupId', Kafka::connection('analytics')->consumer([], 'other-group')));
    }

    #[Test]
    public function producer_throws_exception_if_message_could_not_be_published(): void
    {
        Event::fake();

        $this->expectException(CouldNotPublishMessage::class);

        $this->expectExceptionMessage($expectedMessage = "Your message could not be published. Flush returned with error code -196: 'Local: Communication failure with broker'");

        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR__FAIL)
            ->getMock();

        $this->app->bind(Producer::class, fn () => $mockedProducer);

        try {
            Kafka::publishSync('test')->withBodyKey('foo', 'bar')->send();
        } finally {
            Event::assertDispatched(CouldNotPublishMessageEvent::class, fn (CouldNotPublishMessageEvent $event) => $event->throwable instanceof CouldNotPublishMessage
                && $event->errorCode === RD_KAFKA_RESP_ERR__FAIL
                && $event->message === $expectedMessage);
        }
    }

    #[Test]
    public function macro(): void
    {
        Kafka::macro('ordersProducer', fn () => $this->publish('orders')->withHeaders(['source' => 'macro']));

        $producer = Kafka::ordersProducer();

        $this->assertInstanceOf(PendingMessage::class, $producer);
        $this->assertSame('orders', $producer->getMessage()->getTopicName());
    }

    #[Test]
    public function it_stores_published_messages_when_using_macros(): void
    {
        $expectedMessage = (new Message)
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->onTopic('topic')
            ->withKey(Str::uuid()->toString());

        Kafka::macro('testProducer', fn () => $this->publish()->withMessage($expectedMessage));

        Kafka::fake();
        Kafka::testProducer()->send();

        Kafka::assertPublished($expectedMessage);
    }

    /**
     * Bind a mocked rdkafka producer and return a closure resolving the configuration it was created with.
     *
     * @return Closure(): Conf
     */
    private function mockRdKafkaProducer(ProducerTopic $topic): Closure
    {
        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')->andReturn($topic)
            ->shouldReceive('poll')
            ->shouldReceive('flush')->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $conf = null;

        $this->app->bind(Producer::class, function ($app, array $parameters) use ($mockedProducer, &$conf) {
            $conf = $parameters['conf'];

            return $mockedProducer;
        });

        return function () use (&$conf): Conf {
            return $conf;
        };
    }
}
