<?php

namespace Junges\Kafka\Tests;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Logger;
use Junges\Kafka\Producers\Producer;
use Junges\Kafka\Providers\LaravelKafkaServiceProvider;
use Mockery as m;
use Orchestra\Testbench\TestCase as Orchestra;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\Producer as KafkaProducer;

class LaravelKafkaTestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        (new LaravelKafkaServiceProvider($this->app))->boot();

        app()->instance(Logger::class, $this->getMockedLogger());
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('kafka.clusters.default.brokers', 'localhost:9092');
        $app['config']->set('kafka.clusters.default.partition', 0);
        $app['config']->set('kafka.clusters.default.compression', 'snappy');
        $app['config']->set('kafka.clusters.default.debug', false);
        $app['config']->set('kafka.clusters.default.security_protocol', 'plaintext');
        $app['config']->set('kafka.clusters.default.options', []);

        $app['config']->set('kafka.consumers.default.brokers', 'localhost:9092');
        $app['config']->set('kafka.consumers.default.topics', ['topic1', 'topic2']);
        $app['config']->set('kafka.consumers.default.dlq_topic', 'topic_dlq');
        $app['config']->set('kafka.consumers.default.group_id', 'default');
        $app['config']->set('kafka.consumers.default.offset_reset', 'latest');
        $app['config']->set('kafka.consumers.default.auto_commit', true);
        $app['config']->set('kafka.consumers.default.max_commit_retries', 10);
        $app['config']->set('kafka.consumers.default.commit_batch_size', null);
        $app['config']->set('kafka.consumers.default.max_messages', 2);
        $app['config']->set('kafka.consumers.default.security_protocol', 'plaintext');
        $app['config']->set('kafka.consumers.default.options', []);
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

    protected function mockConsumerWithMessage(Message ...$message)
    {
        $mockedKafkaConsumer = m::mock(KafkaConsumer::class)
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturnUsing(function () use (&$message) {
                return array_splice($message, 0, 1)[0] ?? null;
            })
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

    private function getMockedLogger(): m\MockInterface | m\LegacyMockInterface | null
    {
        return m::mock(Logger::class)
            ->shouldReceive('error')
            ->withAnyArgs()
            ->andReturn()
            ->getMock();
    }

    protected function getConsumerMessage(Message $message): KafkaConsumerMessage
    {
        return app(KafkaConsumerMessage::class, [
            'topicName' => $message->topic_name,
            'partition' => $message->partition,
            'headers' => $message->headers,
            'body' => $message->payload,
            'key' => $message->key,
            'offset' => $message->offset,
            'timestamp' => $message->timestamp,
        ]);
    }
}
