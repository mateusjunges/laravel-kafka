<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Illuminate\Support\Facades\Cache;
use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Contracts\Consumer as ConsumerContract;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Tests\Fakes\FakeKafkaConsumer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use LogicException;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaConsumer;
use RdKafka\KafkaConsumerTopic;
use RdKafka\Message;
use RdKafka\TopicPartition;

final class ConsumerControlTest extends LaravelKafkaTestCase
{
    #[Test]
    public function consumers_are_named_after_their_topics_by_default(): void
    {
        $consumer = Builder::create(new ConnectionConfig('analytics', 'broker', groupId: 'group'), ['orders', 'payments'])->build();

        $this->assertSame('orders,payments', $consumer->getName());
        $this->assertSame('analytics', $consumer->getConnectionName());
        $this->assertSame('group', $consumer->getGroupId());
        $this->assertSame(['orders', 'payments'], $consumer->getTopics());
    }

    #[Test]
    public function consumers_assigned_to_partitions_are_named_after_their_topics_by_default(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))
            ->assignPartitions([new TopicPartition('orders', 0), new TopicPartition('orders', 1)])
            ->build();

        $this->assertSame('orders', $consumer->getName());
    }

    #[Test]
    public function consumers_can_be_named(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'), ['orders'])->withName('order-processor')->build();

        $this->assertSame('order-processor', $consumer->getName());
    }

    #[Test]
    public function consumer_classes_are_named_after_their_class_by_default(): void
    {
        $this->assertSame(FakeKafkaConsumer::class, Kafka::consumerFor(new FakeKafkaConsumer)->build()->getName());
    }

    #[Test]
    public function faked_consumers_are_named_like_real_ones(): void
    {
        Kafka::fake();

        $consumer = Kafka::consumer(['orders'], 'group')->withName('order-processor')->build();

        $this->assertSame('order-processor', $consumer->getName());
        $this->assertSame('default', $consumer->getConnectionName());
        $this->assertSame('group', $consumer->getGroupId());
        $this->assertSame(['orders'], $consumer->getTopics());
    }

    #[Test]
    public function it_pauses_and_resumes_the_assigned_partitions(): void
    {
        $assigned = [new TopicPartition('orders', 0), new TopicPartition('orders', 1)];
        $some = [new TopicPartition('orders', 1)];

        $kafkaConsumer = $this->mockKafkaConsumer();
        $kafkaConsumer->shouldReceive('subscribe');
        $kafkaConsumer->shouldReceive('getAssignment')->andReturn($assigned);
        $kafkaConsumer->shouldReceive('consume')->andReturn($this->makeMessage());
        $kafkaConsumer->shouldReceive('pausePartitions')->once()->with($assigned)->andReturn($assigned);
        $kafkaConsumer->shouldReceive('resumePartitions')->once()->with($some)->andReturn($some);

        $this->app->bind(KafkaConsumer::class, fn () => $kafkaConsumer);

        Builder::create(new ConnectionConfig('default', 'broker'), ['orders'])
            ->withHandler(function (ConsumerMessage $message, ConsumerContract $consumer) use ($some) {
                $consumer->pause();
                $consumer->resume($some);
            })
            ->stopAfterMessages(1)
            ->build()
            ->consume();
    }

    #[Test]
    public function partitions_can_only_be_paused_while_consuming(): void
    {
        $this->expectException(LogicException::class);

        Builder::create(new ConnectionConfig('default', 'broker'), ['orders'])->build()->pause();
    }

    #[Test]
    public function it_restarts_consumers_by_name(): void
    {
        $this->mockConsumerWithEndlessMessages();

        $restarted = $this->runUntilRestarted('orders', fn () => $this->artisan('kafka:restart-consumers', ['consumers' => ['orders']]));

        $this->assertTrue($restarted);
    }

    #[Test]
    public function it_does_not_restart_consumers_with_other_names(): void
    {
        $this->mockConsumerWithEndlessMessages();

        $restarted = $this->runUntilRestarted('orders', fn () => $this->artisan('kafka:restart-consumers', ['consumers' => ['payments']]));

        $this->assertFalse($restarted);
    }

    #[Test]
    public function it_restarts_consumer_classes_by_class_name(): void
    {
        $this->artisan('kafka:restart-consumers', ['consumers' => [FakeKafkaConsumer::class]])
            ->expectsOutputToContain(FakeKafkaConsumer::class)
            ->assertSuccessful();

        $this->assertNotNull(Cache::driver(config('kafka.cache_driver'))->get(Consumer::restartCacheKey(FakeKafkaConsumer::class)));
    }

    /**
     * Run a consumer with the given name, which asks consumers to restart while handling its first message, and
     * stops after its tenth message otherwise. The consumer checks for restarts every second, so it stops after
     * about five messages when restarted. Returns whether the consumer stopped before its tenth message.
     */
    private function runUntilRestarted(string $name, callable $restart): bool
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'), ['orders'])
            ->withName($name)
            ->withHandler(function (ConsumerMessage $message) use ($restart) {
                if ($message->getOffset() === 0) {
                    $restart();
                }

                usleep(200 * 1000);
            })
            ->stopAfterMessages(10)
            ->build();

        $consumer->consume();

        return $consumer->consumedMessagesCount() < 10;
    }

    private function mockConsumerWithEndlessMessages(): void
    {
        $offset = 0;

        $kafkaConsumer = $this->mockKafkaConsumer();
        $kafkaConsumer->shouldReceive('subscribe');
        $kafkaConsumer->shouldReceive('newTopic')->andReturn(m::mock(KafkaConsumerTopic::class, ['offsetStore' => null]));
        $kafkaConsumer->shouldReceive('consume')->andReturnUsing(function () use (&$offset) {
            return $this->makeMessage($offset++);
        });

        $this->app->bind(KafkaConsumer::class, fn () => $kafkaConsumer);
    }

    private function makeMessage(int $offset = 0): Message
    {
        $message = new Message;
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $message->key = null;
        $message->topic_name = 'orders';
        $message->payload = '{}';
        $message->offset = $offset;
        $message->partition = 0;
        $message->headers = [];

        return $message;
    }
}
