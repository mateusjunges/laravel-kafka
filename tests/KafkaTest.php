<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Consumers\Builder as ConsumerBuilder;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Events\BatchMessagePublished;
use Junges\Kafka\Events\CouldNotPublishMessage as CouldNotPublishMessageEvent;
use Junges\Kafka\Events\MessageBatchPublished;
use Junges\Kafka\Events\MessagePublished;
use Junges\Kafka\Events\PublishingMessageBatch;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use Junges\Kafka\Exceptions\CouldNotPublishMessageBatch;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Message\Serializers\JsonSerializer;
use Junges\Kafka\Producers\Builder as ProducerBuilder;
use Junges\Kafka\Producers\MessageBatch;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\Producer;
use RdKafka\ProducerTopic;

final class KafkaTest extends LaravelKafkaTestCase
{
    public function testItCanPublishMessagesToKafka(): void
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

        $this->app->terminate();

        Event::assertDispatched(MessagePublished::class);

        $this->assertTrue($test1);
        $this->assertTrue($test2);
    }

    public function testICanSwitchSerializersOnTheFly(): void
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
            ->usingSerializer(new JsonSerializer())
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withDebugEnabled();


        $test = $producer->send();

        $this->assertTrue($test);

        $serializer = $this->getPropertyWithReflection('serializer', $producer);

        $this->assertInstanceOf(JsonSerializer::class, $serializer);
    }

    public function testItDoesNotSendMessagesToKafkaIfUsingFake(): void
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

    public function testICanSetTheEntireMessageWithMessageObject(): void
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

    public function testICanDisableDebugUsingWithDebugDisabledMethod(): void
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

    public function testICanUseCustomOptionsForProducerConfig(): void
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

    public function testCreateConsumerReturnsAConsumerBuilderInstance(): void
    {
        $consumer = Kafka::consumer();

        $this->assertInstanceOf(ConsumerBuilder::class, $consumer);
    }

    public function testCreateConsumerDefaultConfigs(): void
    {
        $consumer = Kafka::consumer();

        $this->assertInstanceOf(ConsumerBuilder::class, $consumer);
        $this->assertEquals('group', $this->getPropertyWithReflection('groupId', $consumer));
        $this->assertEquals('localhost:9092', $this->getPropertyWithReflection('brokers', $consumer));
        $this->assertEquals([], $this->getPropertyWithReflection('topics', $consumer));
    }

    public function testProducerThrowsExceptionIfMessageCouldNotBePublished(): void
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

    public function testSendMessageBatch(): void
    {
        $messageBatch = new MessageBatch;
        $messageBatch->push(new Message('test_1'));
        $messageBatch->push(new Message('test_2'));
        $messageBatch->push(new Message('test_3'));

        $expectedUuid = $messageBatch->getBatchUuid();

        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')
            ->times($messageBatch->getMessages()->count())
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')->with('test_1')->once()->andReturn($mockedProducerTopic)
            ->shouldReceive('newTopic')->with('test_2')->once()->andReturn($mockedProducerTopic)
            ->shouldReceive('newTopic')->with('test_3')->once()->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')
            ->times($messageBatch->getMessages()->count())
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->once()
            ->getMock();

        $this->app->bind(Producer::class, function () use ($mockedProducer) {
            return $mockedProducer;
        });

        Event::fake();

        Kafka::publish()->withBodyKey('foo', 'bar')->sendBatch($messageBatch);

        Event::assertDispatched(PublishingMessageBatch::class, function (PublishingMessageBatch $event) use ($messageBatch) {
            return $event->batch === $messageBatch;
        });
        Event::assertDispatchedTimes(BatchMessagePublished::class, 3);
        Event::assertDispatched(BatchMessagePublished::class, function (BatchMessagePublished $event) use ($expectedUuid) {
            return $event->batchUuid === $expectedUuid;
        });
        Event::assertDispatched(MessageBatchPublished::class, function (MessageBatchPublished $event) use ($messageBatch) {
            return $event->batch === $messageBatch
                && $event->publishedCount === 3;
        });
    }

    #[Test]
    public function it_throws_an_exception_if_there_is_a_message_in_batch_with_no_topic_specified(): void
    {
        $messageBatch = new MessageBatch;
        $messageBatch->push(new Message('test_1'));
        $messageBatch->push(new Message('test_2'));
        $messageBatch->push(new Message);

        $this->expectException(CouldNotPublishMessageBatch::class);
        $this->expectExceptionMessage("The provided topic name [''] is invalid for the message batch. Try again with a valid topic name.");

        Kafka::publish()->sendBatch($messageBatch);
    }

    public function testMacro(): void
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

    /** @test */
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
