<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use InvalidArgumentException;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Tests\Fakes\FakeKafkaConsumer;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

final class KafkaConsumerTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_builds_a_consumer_from_the_class_properties(): void
    {
        config(['kafka.connections.analytics' => [
            'brokers' => 'analytics:9092',
            'consumer' => ['group_id' => 'analytics-group'],
        ]]);

        $consumer = new FakeKafkaConsumer;
        $consumer->connection = 'analytics';
        $consumer->group = 'orders-group';
        $consumer->retries = 3;
        $consumer->backoff = 500;
        $consumer->dlq = 'orders-failed';
        $consumer->skipFailedMessages = true;

        $builder = Kafka::consumerFor($consumer);

        $this->assertSame(['orders'], $this->getPropertyWithReflection('topics', $builder));
        $this->assertSame('analytics:9092', $this->getPropertyWithReflection('brokers', $builder));
        $this->assertSame('orders-group', $this->getPropertyWithReflection('groupId', $builder));
        $this->assertSame(3, $this->getPropertyWithReflection('failedMessageRetries', $builder));
        $this->assertSame(500, $this->getPropertyWithReflection('failedMessageRetryBackoff', $builder));
        $this->assertSame('orders-failed', $this->getPropertyWithReflection('dlq', $builder));
        $this->assertTrue($this->getPropertyWithReflection('skipFailedMessages', $builder));
        $this->assertCount(1, $this->getPropertyWithReflection('middlewares', $builder));
        $this->assertTrue($consumer->configured);
        $this->assertSame(600000, $this->getPropertyWithReflection('options', $builder)['max.poll.interval.ms']);
    }

    #[Test]
    public function it_uses_the_connection_defaults_when_properties_are_not_set(): void
    {
        $builder = Kafka::consumerFor(new FakeKafkaConsumer);

        $this->assertSame('localhost:9092', $this->getPropertyWithReflection('brokers', $builder));
        $this->assertSame('group', $this->getPropertyWithReflection('groupId', $builder));
        $this->assertNull($this->getPropertyWithReflection('dlq', $builder));
        $this->assertFalse($this->getPropertyWithReflection('skipFailedMessages', $builder));
    }

    #[Test]
    public function it_uses_the_default_dead_letter_queue_name_when_dlq_is_true(): void
    {
        $consumer = new FakeKafkaConsumer;
        $consumer->dlq = true;

        $this->assertSame('orders-dlq', $this->getPropertyWithReflection('dlq', Kafka::consumerFor($consumer)));
    }

    #[Test]
    public function it_resolves_consumer_classes_from_the_container(): void
    {
        $this->app->instance(FakeKafkaConsumer::class, $consumer = new FakeKafkaConsumer);

        Kafka::consumerFor(FakeKafkaConsumer::class);

        $this->assertTrue($consumer->configured);
    }

    #[Test]
    public function it_only_accepts_kafka_consumer_classes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Kafka::consumerFor(stdClass::class);
    }

    #[Test]
    public function consumer_classes_can_be_tested_with_the_kafka_fake(): void
    {
        Kafka::fake();
        Kafka::shouldReceiveMessages([
            new ConsumedMessage('orders', 0, [], ['id' => 1], null, 0, null),
            new ConsumedMessage('orders', 0, [], ['id' => 2], null, 1, null),
        ]);

        $consumer = new FakeKafkaConsumer;

        Kafka::consumerFor($consumer)->build()->consume();

        $this->assertSame([1, 2], array_map(fn (ConsumerMessage $message) => $message->getBody()['id'], $consumer->handled));
        $this->assertSame(['middleware', 'middleware'], $consumer->middlewareCalls);
    }
}
