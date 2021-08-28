<?php

namespace Junges\Kafka\Tests;

use Junges\Kafka\Producers\Producer;
use Junges\Kafka\Providers\LaravelKafkaServiceProvider;
use Mockery as m;
use Orchestra\Testbench\TestCase as Orchestra;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\Producer as KafkaProducer;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        (new LaravelKafkaServiceProvider($this->app))->boot();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelKafkaServiceProvider::class,
        ];
    }

    protected function mockProducer()
    {
        $mockedProducer = m::mock(Producer::class)
            ->shouldReceive('withKey')
            ->withArgs(['key'])
            ->andReturn(m::self())
            ->shouldReceive('withHeaders')
            ->with(['header' => 'header', 'origin' => 'kafka'])
            ->andReturn(m::self())
            ->shouldReceive('produce')
            ->andReturn();

        $this->app->bind(Producer::class, function () use ($mockedProducer) {
            return $mockedProducer->getMock();
        });

        $mockedKafkaProducer = m::mock(KafkaProducer::class)
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(KafkaProducer::class, function () use ($mockedKafkaProducer) {
            return $mockedKafkaProducer;
        });
    }

    protected function mockConsumerWithMessageFailingCommit(Message $message)
    {
        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->never()
            ->getMock();

        $this->app->bind(KafkaConsumer::class, function () use ($mockedKafkaConsumer) {
            return $mockedKafkaConsumer;
        });
    }

    protected function mockConsumerWithMessage(Message $message)
    {
        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->andReturn()
            ->getMock();

        $this->app->bind(KafkaConsumer::class, function () use ($mockedKafkaConsumer) {
            return $mockedKafkaConsumer;
        });
    }

    protected function getPropertyWithReflection(string $property, object $object)
    {
        $reflection = new \ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }
}
