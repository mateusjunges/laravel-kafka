<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Logger;
use Junges\Kafka\Producers\Producer;
use Junges\Kafka\Providers\LaravelKafkaServiceProvider;
use Mockery as m;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\KafkaConsumerTopic;
use RdKafka\Message;
use RdKafka\Producer as KafkaProducer;
use RdKafka\ProducerTopic;
use ReflectionClass;

abstract class LaravelKafkaTestCase extends Orchestra
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        app()->instance(Logger::class, $this->getMockedLogger());
    }

    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('kafka.connections.default', [
            'brokers' => 'localhost:9092',
            'consumer' => [
                'group_id' => 'group',
                'auto_commit' => true,
                'options' => ['auto.offset.reset' => 'latest'],
            ],
        ]);
        $app['config']->set('kafka.cache_driver', 'array');
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelKafkaServiceProvider::class,
        ];
    }

    protected function mockProducer(): void
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

        $this->app->bind(Producer::class, fn () => $mockedProducer->getMock());

        $this->mockKafkaProducer();
    }

    protected function mockKafkaProducer(): void
    {
        // We have to get a topic object as a valid response for the mock
        // We stub out this code here to achieve that
        $conf = new Conf;
        $conf->set('log_level', '0');
        $kafka = new KafkaProducer($conf);
        $topic = $kafka->newTopic('test-topic');

        $mockedKafkaProducer = m::mock(KafkaProducer::class)
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->shouldReceive('newTopic')
            ->andReturn($topic)
            ->shouldReceive('poll')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(KafkaProducer::class, fn () => $mockedKafkaProducer);
    }

    /**
     * Mock Kafka producer specifically for DLQ tests so we can capture and acssess headers.
     */
    protected function mockKafkaProducerForDlq(array $expectedHeaders): void
    {
        $mockedTopic = m::mock(ProducerTopic::class)
            ->shouldReceive('producev')
            ->withArgs(function ($partition, $msgflags, $payload, $key, $headers) use ($expectedHeaders) {
                // check that all expected headers are present
                foreach ($expectedHeaders as $headerKey => $headerValue) {
                    if (! array_key_exists($headerKey, $headers) || $headers[$headerKey] !== $headerValue) {
                        return false;
                    }
                }

                return true;
            })
            ->andReturn()
            ->getMock();

        $mockedKafkaProducer = m::mock(KafkaProducer::class)
            ->shouldReceive('flush')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->shouldReceive('newTopic')
            ->andReturn($mockedTopic)
            ->shouldReceive('poll')
            ->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR)
            ->getMock();

        $this->app->bind(KafkaProducer::class, fn () => $mockedKafkaProducer);
    }

    protected function mockConsumerWithMessageFailingCommit(Message $message): void
    {
        $mockedKafkaConsumer = $this->mockKafkaConsumer()
            ->shouldReceive('subscribe')
            ->andReturn(m::self())
            ->shouldReceive('consume')
            ->withAnyArgs()
            ->andReturn($message)
            ->shouldReceive('commit')
            ->never()
            ->getMock();

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
    }

    protected function mockConsumerWithMessage(Message ...$message): void
    {
        $mockedKafkaConsumer = $this->mockKafkaConsumer()
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

        $this->app->bind(KafkaConsumer::class, fn () => $mockedKafkaConsumer);
    }

    /**
     * Mock a KafkaConsumer that may be closed, as the consumer closes it whenever it stops consuming,
     * and that may store offsets, as it does after processing each message in auto commit mode.
     */
    protected function mockKafkaConsumer(): m\MockInterface
    {
        $topic = m::mock(KafkaConsumerTopic::class);
        $topic->shouldReceive('offsetStore')->byDefault();

        $consumer = m::mock(KafkaConsumer::class);
        $consumer->shouldReceive('close')->byDefault();
        $consumer->shouldReceive('newTopic')->andReturn($topic)->byDefault();

        return $consumer;
    }

    protected function getPropertyWithReflection(string $property, object $object): mixed
    {
        $reflection = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);

        return $reflectionProperty->getValue($object);
    }

    protected function getConsumerMessage(Message $message): ConsumerMessage
    {
        return app(ConsumerMessage::class, [
            'topicName' => $message->topic_name,
            'partition' => $message->partition,
            'headers' => $message->headers,
            'body' => $message->payload,
            'key' => $message->key,
            'offset' => $message->offset,
            'timestamp' => $message->timestamp,
        ]);
    }

    private function getMockedLogger(): m\MockInterface|m\LegacyMockInterface|null
    {
        return m::mock(Logger::class)
            ->shouldReceive('error')
            ->withAnyArgs()
            ->andReturn()
            ->getMock();
    }
}
