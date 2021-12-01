<?php

namespace Junges\Kafka\Tests;

use Illuminate\Support\Str;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Exceptions\CouldNotPublishMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Message\Serializers\NullSerializer;
use Junges\Kafka\Producers\ProducerBuilder;
use Mockery as m;
use RdKafka\Producer;

class KafkaTest extends LaravelKafkaTestCase
{
    public function testItCanPublishMessagesToKafka()
    {
        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn(m::self())
            ->shouldReceive('producev')
            ->andReturn(m::self())
            ->shouldReceive('poll')
            ->andReturn(m::self())
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
        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn(m::self())
            ->shouldReceive('producev')
            ->andReturn(m::self())
            ->shouldReceive('poll')
            ->andReturn(m::self())
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
            ->usingSerializer(new NullSerializer())
            ->withBodyKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withDebugEnabled();


        $test = $producer->send();

        $this->assertTrue($test);

        $serializer = $this->getPropertyWithReflection('serializer', $producer);

        $this->assertInstanceOf(NullSerializer::class, $serializer);
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
        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn(m::self())
            ->shouldReceive('producev')
            ->andReturn(m::self())
            ->shouldReceive('poll')
            ->andReturn(m::self())
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

    public function testICanDisableDebugUsingWithDebugDisabledMethod()
    {
        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn(m::self())
            ->shouldReceive('producev')
            ->andReturn(m::self())
            ->shouldReceive('poll')
            ->andReturn(m::self())
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

        $this->expectExceptionMessage("Sent messages may not be completed yet.");

        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('newTopic')
            ->andReturn(m::self())
            ->shouldReceive('producev')
            ->andReturn(m::self())
            ->shouldReceive('poll')
            ->andReturn(m::self())
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR__FAIL)
            ->times(10)
            ->getMock();

        $this->app->bind(Producer::class, function () use ($mockedProducer) {
            return $mockedProducer;
        });

        Kafka::publishOn('test')->withBodyKey('foo', 'bar')->send();
    }
}
