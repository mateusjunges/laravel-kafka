<?php

namespace Junges\Kafka\Tests\Unit;

use Illuminate\Support\Str;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use RdKafka\Producer;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

class LaravelKafkaTest extends LaravelKafkaTestCase
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

        $test = Kafka::publishOn('localhost:9092', 'test-topic')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withKafkaMessageKey(Str::uuid()->toString())
            ->withMessageKey('test', ['test'])
            ->withHeaders(['custom' => 'header'])
            ->withDebugEnabled()
            ->send();

        $this->assertTrue($test);
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

        $test = Kafka::publishOn('localhost:9092', 'test-topic')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withKafkaMessageKey(Str::uuid()->toString())
            ->withMessageKey('test', ['test'])
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

        $test = Kafka::publishOn('localhost:9092', 'test-topic')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withMessage(new Message(
                headers: ['foo' => 'bar'],
                message: ['foo' => 'bar'],
                key: 'message-key'
            ))
            ->withDebugEnabled()
            ->send();

        $this->assertTrue($test);

        $test = Kafka::publishOn('localhost:9092', 'test-topic')
            ->withConfigOptions([
                'metadata.broker.list' => 'broker',
            ])
            ->withMessage(new Message(
                headers: ['foo' => 'bar'],
                message: ['foo' => 'bar'],
                key: 'message-key'
            ))
            ->withDebugEnabled(false)
            ->send();

        $this->assertTrue($test);
    }

    public function testCreateConsumerReturnsAConsumerBuilderInstance()
    {
        $consumer = Kafka::createConsumer('broker', ['topic']);

        $this->assertInstanceOf(ConsumerBuilder::class, $consumer);
    }
}
