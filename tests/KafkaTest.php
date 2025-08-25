<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Consumers\Builder as ConsumerBuilder;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Events\CouldNotPublishMessage as CouldNotPublishMessageEvent;
use Junges\Kafka\Events\MessagePublished;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Message\Serializers\JsonSerializer;
use Junges\Kafka\Producers\Builder as ProducerBuilder;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
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

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(Producer::class, fn () => $mockedProducer);

        $test = Kafka::publish()
            ->onTopic('test')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withKafkaKey(Str::uuid()->toString())
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withDebugEnabled()
            ->send();

        Event::assertDispatched(MessagePublished::class);

        $this->assertTrue($test);
    }

    #[Test]
    public function it_can_publish_messages_asynchronously(): void
    {
        Event::fake();

        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->twice()
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')->with('test')->twice()->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')->twice()
            ->shouldReceive('flush')->once()
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(Producer::class, fn () => $mockedProducer);

        $test1 = Kafka::asyncPublish()
            ->onTopic('test')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withKafkaKey(Str::uuid()->toString())
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withDebugEnabled()
            ->send();

        $test2 = Kafka::asyncPublish()
            ->onTopic('test')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withKafkaKey(Str::uuid()->toString())
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withDebugEnabled()
            ->send();

        Event::assertDispatched(MessagePublished::class);

        $this->assertTrue($test1);
        $this->assertTrue($test2);

        Kafka::clearResolvedInstances();

        Event::assertDispatched(MessagePublished::class);
    }

    #[Test]
    public function i_can_switch_serializers_on_the_fly(): void
    {
        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(Producer::class, fn () => $mockedProducer);

        $producer = Kafka::publish()->onTopic('test-topic')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withKafkaKey(Str::uuid()->toString())
            ->usingSerializer(new JsonSerializer)
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withDebugEnabled();

        $test = $producer->send();

        $this->assertTrue($test);

        $serializer = $this->getPropertyWithReflection('serializer', $producer);

        $this->assertInstanceOf(JsonSerializer::class, $serializer);
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

        $test = Kafka::publish()
            ->onTopic('test-topic')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withKafkaKey(Str::uuid()->toString())
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withDebugEnabled()
            ->send();

        $this->assertTrue($test);
    }

    #[Test]
    public function i_can_set_the_entire_message_with_message_object(): void
    {
        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->times(2)
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(Producer::class, function () use ($mockedProducer) {
            return $mockedProducer;
        });

        $message = Message::create()
            ->withHeaders(['foo' => 'bar'])
            ->onTopic('test')
            ->withKey('message-key')
            ->onTopic('test')
            ->withBody(['foo' => 'bar']);

        $test = Kafka::publish()
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withMessage($message)
            ->withDebugEnabled()
            ->send();

        $this->assertTrue($test);

        $test = Kafka::publish()
            ->onTopic('test')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withMessage(new Message(
                headers: ['foo' => 'bar'],
                body: ['foo' => 'bar'],
                key: 'message-key',
            ))
            ->withDebugEnabled(false)
            ->send();

        $this->assertTrue($test);
    }

    #[Test]
    public function i_can_disable_debug_using_with_debug_disabled_method(): void
    {
        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')->once()
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(Producer::class, function () use ($mockedProducer) {
            return $mockedProducer;
        });

        /** @var ProducerBuilder $producer */
        $producer = Kafka::publish()
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withKafkaKey(Str::uuid()->toString())
            ->withBodyKey('test', ['test'])
            ->onTopic('test')
            ->withHeaders(['custom' => 'header'])
            ->withDebugDisabled();

        $test = $producer->send();

        $this->assertTrue($test);

        $message = $this->getPropertyWithReflection('message', $producer);

        $this->assertInstanceOf(ProducerMessage::class, $message);

        $this->assertArrayNotHasKey('log_level', $message->getHeaders());
        $this->assertArrayNotHasKey('debug', $message->getHeaders());
    }

    #[Test]
    public function i_can_use_custom_options_for_producer_config(): void
    {
        $producer = Kafka::publish()
            ->withConfigOptions($expectedOptions = [
                'bootstrap.servers' => '[REMOTE_ADDRESS]',
                'metadata.broker.list' => '[REMOTE_ADDRESS]',
                'security.protocol' => 'SASL_SSL',
                'sasl.mechanisms' => 'PLAIN',
                'sasl.username' => '[API_KEY]',
                'sasl.password' => '[API_KEY]',
            ]);

        $options = $this->getPropertyWithReflection('options', $producer);

        $this->assertEquals($expectedOptions, $options);
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
            ->times(10)
            ->getMock();

        $this->app->bind(Producer::class, fn () => $mockedProducer);

        Kafka::publish()->onTopic('test')->withBodyKey('foo', 'bar')->send();

        Event::assertDispatched(CouldNotPublishMessageEvent::class, function (CouldNotPublishMessageEvent $event) use ($expectedMessage) {
            return $event->throwable instanceof CouldNotPublishMessage
                && $event->errorCode === RD_KAFKA_RESP_ERR__FAIL
                && $event->message === $expectedMessage;
        });
    }

    #[Test]
    public function macro(): void
    {
        $sasl = new Sasl(username: 'username', password: 'password', mechanisms: 'mechanisms');

        Kafka::macro('defaultProducer', function () {
            return $this->publish()->withSasl(
                username: 'username',
                password: 'password',
                mechanisms: 'mechanisms',
            );
        });

        $producer = Kafka::defaultProducer();

        $this->assertInstanceOf(ProducerBuilder::class, $producer);
        $this->assertEquals($sasl, $this->getPropertyWithReflection('saslConfig', $producer));
    }

    #[Test]
    public function it_stores_published_messages_when_using_macros(): void
    {
        $expectedMessage = (new Message)
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->onTopic('topic')
            ->withKey($uuid = Str::uuid()->toString());

        Kafka::macro('testProducer', function () use ($expectedMessage) {
            return $this->publish()->withMessage($expectedMessage);
        });

        Kafka::fake();
        Kafka::testProducer()->send();

        Kafka::assertPublished($expectedMessage);
    }
}
