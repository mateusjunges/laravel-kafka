<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Illuminate\Support\Facades\Event;
use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Consumers\StopReason;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Events\ConsumerStarting;
use Junges\Kafka\Events\ConsumerStopped;
use Junges\Kafka\Events\MessageFailed;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Tests\Fakes\FakeKafkaConsumer;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class ConsumerConfigurationTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_configures_every_consumer_when_it_is_created(): void
    {
        $configured = [];

        Kafka::configureConsumersUsing(function (Builder $builder) use (&$configured) {
            $configured[] = $builder;

            $builder->withOption('statistics.interval.ms', 5000);
        });

        config(['kafka.connections.analytics' => ['brokers' => 'analytics:9092']]);

        $default = Kafka::consumer(['orders']);
        $analytics = Kafka::connection('analytics')->consumer(['page-views']);

        $this->assertSame([$default, $analytics], $configured);
        $this->assertSame(5000, $this->getPropertyWithReflection('options', $default)['statistics.interval.ms']);
        $this->assertSame(5000, $this->getPropertyWithReflection('options', $analytics)['statistics.interval.ms']);
    }

    #[Test]
    public function the_configuration_of_each_consumer_takes_precedence(): void
    {
        Kafka::configureConsumersUsing(fn (Builder $builder) => $builder->withOption('max.poll.interval.ms', 1000));

        // The fake consumer class sets "max.poll.interval.ms" to 600000 in its configure() method.
        $builder = Kafka::consumerFor(new FakeKafkaConsumer);

        $this->assertSame(600000, $this->getPropertyWithReflection('options', $builder)['max.poll.interval.ms']);
    }

    #[Test]
    public function it_applies_configuration_callbacks_registered_after_the_connection_is_resolved(): void
    {
        Kafka::connection();

        Kafka::configureConsumersUsing(fn (Builder $builder) => $builder->withName('configured'));

        $this->assertSame('configured', Kafka::consumer(['orders'])->build()->getName());
    }

    #[Test]
    public function the_kafka_fake_keeps_the_configuration_callbacks(): void
    {
        $calls = 0;

        Kafka::configureConsumersUsing(function (Builder $builder) use (&$calls) {
            $builder->beforeConsuming(function (Consumer $consumer) use (&$calls) {
                $calls++;
            });
        });

        Kafka::fake();
        Kafka::shouldReceiveMessages([new ConsumedMessage('orders', 0, [], ['id' => 1], null, 0, null)]);

        Kafka::consumer(['orders'])->build()->consume();

        $this->assertSame(1, $calls);
    }

    #[Test]
    public function faked_consumers_dispatch_the_lifecycle_events(): void
    {
        Event::fake();
        Kafka::fake();
        Kafka::shouldReceiveMessages([new ConsumedMessage('orders', 0, [], ['id' => 1], null, 0, null)]);

        $consumer = Kafka::consumer(['orders'])->build();
        $consumer->consume();

        Event::assertDispatched(ConsumerStarting::class, fn (ConsumerStarting $event) => $event->consumer === $consumer);
        Event::assertDispatched(ConsumerStopped::class, fn (ConsumerStopped $event) => $event->consumer === $consumer && $event->reason === StopReason::Empty);
    }

    #[Test]
    public function faked_consumers_dispatch_the_failed_and_stopped_events_when_a_message_fails(): void
    {
        Event::fake();
        Kafka::fake();
        Kafka::shouldReceiveMessages([new ConsumedMessage('orders', 0, [], ['id' => 1], null, 0, null)]);

        $consumer = Kafka::consumer(['orders'])->withHandler(fn () => throw new RuntimeException('fail'))->build();

        try {
            $consumer->consume();

            $this->fail('The consumer should stop when a message fails.');
        } catch (ConsumerException $exception) {
            Event::assertDispatched(MessageFailed::class, fn (MessageFailed $event) => $event->consumer === $consumer);
            Event::assertDispatched(ConsumerStopped::class, fn (ConsumerStopped $event) => $event->reason === StopReason::Failed && $event->exception === $exception);
        }
    }
}
