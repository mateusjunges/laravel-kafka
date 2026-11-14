<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Tests\Fakes\FakeKafkaConsumer;
use Junges\Kafka\Tests\Fakes\FakeMiddleware;
use PHPUnit\Framework\Attributes\Test;

final class ConsumerMiddlewareTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_adds_global_middlewares_to_every_consumer(): void
    {
        $global = fn (ConsumerMessage $message, callable $next) => $next($message);

        Kafka::consumerMiddleware([$global, FakeMiddleware::class]);

        $this->assertSame([$global, FakeMiddleware::class], $this->getPropertyWithReflection('middlewares', Kafka::consumer(['orders'])));

        config(['kafka.connections.analytics' => ['brokers' => 'analytics:9092']]);

        $this->assertSame([$global, FakeMiddleware::class], $this->getPropertyWithReflection('middlewares', Kafka::connection('analytics')->consumer()));
    }

    #[Test]
    public function it_applies_middlewares_registered_after_the_connection_is_resolved(): void
    {
        Kafka::connection();

        Kafka::consumerMiddleware(FakeMiddleware::class);

        $this->assertSame([FakeMiddleware::class], $this->getPropertyWithReflection('middlewares', Kafka::consumer()));
    }

    #[Test]
    public function global_middlewares_run_before_the_middlewares_of_each_consumer(): void
    {
        $calls = [];

        Kafka::consumerMiddleware(function (ConsumerMessage $message, callable $next) use (&$calls) {
            $calls[] = 'global';

            return $next($message);
        });

        Kafka::fake();
        Kafka::shouldReceiveMessages([new ConsumedMessage('orders', 0, [], ['id' => 1], null, 0, null)]);

        Kafka::consumer(['orders'])
            ->withMiddleware(function (ConsumerMessage $message, callable $next) use (&$calls) {
                $calls[] = 'consumer';

                return $next($message);
            })
            ->withHandler(function () use (&$calls) {
                $calls[] = 'handler';
            })
            ->build()
            ->consume();

        $this->assertSame(['global', 'consumer', 'handler'], $calls);
    }

    #[Test]
    public function the_kafka_fake_keeps_the_global_middlewares(): void
    {
        FakeMiddleware::$messages = [];

        Kafka::consumerMiddleware(FakeMiddleware::class);

        Kafka::fake();
        Kafka::shouldReceiveMessages([new ConsumedMessage('orders', 0, [], ['id' => 1], null, 0, null)]);

        $consumer = new FakeKafkaConsumer;

        Kafka::consumerFor($consumer)->build()->consume();

        $this->assertCount(1, FakeMiddleware::$messages);
        $this->assertCount(1, $consumer->handled);
    }
}
