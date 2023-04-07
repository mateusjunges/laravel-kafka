<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Illuminate\Support\Str;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Message\Serializers\JsonSerializer;
use Junges\Kafka\Producers\MessageBatch;
use Junges\Kafka\Producers\ProducerBuilder;
use Mockery as m;
use RdKafka\Producer;
use RdKafka\ProducerTopic;

final class KafkaTest extends LaravelKafkaTestCase
{
    public function testItCanPublishMessagesToKafka(): void
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

        $test = Kafka::publishOn('test-topic')
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

        $producer = Kafka::publishOn('test-topic')
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

        $test = Kafka::publishOn('test-topic')
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

        $message = Message::create()->withHeaders(['foo' => 'bar'])->withKey('message-key')->withBody(['foo' => 'bar']);

        $test = Kafka::publishOn('test-topic')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withMessage($message)
            ->withDebugEnabled()
            ->send();

        $this->assertTrue($test);

        $test = Kafka::publishOn('test-topic')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withMessage(new Message(
                headers: ['foo' => 'bar'],
                body: ['foo' => 'bar'],
                key: 'message-key'
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
        $producer = Kafka::publishOn('test-topic')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withKafkaKey(Str::uuid()->toString())
            ->withBodyKey('test', ['test'])
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
        $producer = Kafka::publishOn('test-topic')
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
        $consumer = Kafka::createConsumer();

        $this->assertInstanceOf(ConsumerBuilder::class, $consumer);
    }

    public function testCreateConsumerDefaultConfigs(): void
    {
        $consumer = Kafka::createConsumer();

        $this->assertInstanceOf(ConsumerBuilder::class, $consumer);
        $this->assertEquals('group', $this->getPropertyWithReflection('groupId', $consumer));
        $this->assertEquals('localhost:9092', $this->getPropertyWithReflection('brokers', $consumer));
        $this->assertEquals([], $this->getPropertyWithReflection('topics', $consumer));
    }

    public function testProducerThrowsExceptionIfMessageCouldNotBePublished(): void
    {
        $this->expectException(CouldNotPublishMessage::class);

        $this->expectExceptionMessage("Your message could not be published. Flush returned with error code -196: 'Local: Communication failure with broker'");

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

        Kafka::publishOn('test')->withBodyKey('foo', 'bar')->send();
    }

    public function testSendMessageBatch(): void
    {
        $messageBatch = new MessageBatch();
        $messageBatch->push(new Message());
        $messageBatch->push(new Message());
        $messageBatch->push(new Message());

        $mockedProducerTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')
            ->times($messageBatch->getMessages()->count())
            ->andReturn(m::self())
            ->getMock();

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn($mockedProducerTopic)
            ->shouldReceive('poll')
            ->times($messageBatch->getMessages()->count())
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->once()
            ->getMock();

        $this->app->bind(Producer::class, function () use ($mockedProducer) {
            return $mockedProducer;
        });

        Kafka::publishOn('test')->withBodyKey('foo', 'bar')->sendBatch($messageBatch);
    }
}
