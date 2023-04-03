<?php

namespace Junges\Kafka\Tests;

use Illuminate\Support\Str;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Message\Serializers\JsonSerializer;
use Junges\Kafka\Producers\MessageBatch;
use Junges\Kafka\Producers\ProducerBuilder;
use Mockery as m;
use RdKafka\Producer;
use RdKafka\ProducerTopic;

class KafkaTest extends LaravelKafkaTestCase
{
    public function testItCanPublishMessagesToKafka()
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

    public function testICanSwitchSerializersOnTheFly()
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

    public function testItDoesNotSendMessagesToKafkaIfUsingFake()
    {
        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')->never()
            ->shouldReceive('producev')->never()
            ->shouldReceive('poll')->never()
            ->shouldReceive('flush')->never()
            ->getMock();

        $this->app->bind(Producer::class, function () use ($mockedProducer) {
            return $mockedProducer;
        });

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

    public function testICanSetTheEntireMessageWithMessageObject()
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
                null,
                -1,
                ['foo' => 'bar'],
                ['foo' => 'bar'],
                'message-key'
            ))
            ->withDebugEnabled(false)
            ->send();

        $this->assertTrue($test);
    }

    public function testICanDisableDebugUsingWithDebugDisabledMethod()
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

        $this->assertInstanceOf(KafkaProducerMessage::class, $message);

        $this->assertArrayNotHasKey('log_level', $message->getHeaders());
        $this->assertArrayNotHasKey('debug', $message->getHeaders());
    }

    public function testICanUseCustomOptionsForProducerConfig()
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

    public function testCreateConsumerReturnsAConsumerBuilderInstance()
    {
        $consumer = Kafka::createConsumer();

        $this->assertInstanceOf(ConsumerBuilder::class, $consumer);
    }

    public function testCreateConsumerDefaultConfigs()
    {
        $consumer = Kafka::createConsumer();

        $this->assertInstanceOf(ConsumerBuilder::class, $consumer);
        $this->assertEquals('group', $this->getPropertyWithReflection('groupId', $consumer));
        $this->assertEquals('localhost:9092', $this->getPropertyWithReflection('brokers', $consumer));
        $this->assertEquals([], $this->getPropertyWithReflection('topics', $consumer));
    }

    public function testProducerThrowsExceptionIfMessageCouldNotBePublished()
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

        $this->app->bind(Producer::class, function () use ($mockedProducer) {
            return $mockedProducer;
        });

        Kafka::publishOn('test')->withBodyKey('foo', 'bar')->send();
    }

    public function testSendMessageBatch()
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
