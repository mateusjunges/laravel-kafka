<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Console\Consumers;

use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Tests\Fakes\FakeKafkaConsumer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;

final class KafkaConsumerCommandTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_consumes_messages_using_a_consumer_class(): void
    {
        $consumer = $this->fakeConsumerReceiving(2);

        $this->artisan('kafka:consume', ['consumer' => FakeKafkaConsumer::class])->assertSuccessful();

        $this->assertCount(2, $consumer->handled);
    }

    #[Test]
    public function it_resolves_consumers_in_the_app_kafka_consumers_namespace(): void
    {
        class_alias(FakeKafkaConsumer::class, 'App\\Kafka\\Consumers\\OrdersConsumer');

        $consumer = $this->fakeConsumerReceiving(1);
        $this->app->instance('App\\Kafka\\Consumers\\OrdersConsumer', $consumer);

        $this->artisan('kafka:consume', ['consumer' => 'OrdersConsumer'])->assertSuccessful();

        $this->assertCount(1, $consumer->handled);
    }

    #[Test]
    public function it_stops_after_the_given_number_of_messages(): void
    {
        $consumer = $this->fakeConsumerReceiving(3);

        $this->artisan('kafka:consume', ['consumer' => FakeKafkaConsumer::class, '--max-messages' => 1])->assertSuccessful();

        $this->assertCount(1, $consumer->handled);
    }

    #[Test]
    public function it_fails_when_the_consumer_class_does_not_exist(): void
    {
        $this->artisan('kafka:consume', ['consumer' => 'MissingConsumer'])
            ->expectsOutputToContain('The consumer [MissingConsumer] does not exist')
            ->assertFailed();
    }

    private function fakeConsumerReceiving(int $messages): FakeKafkaConsumer
    {
        Kafka::fake();
        Kafka::shouldReceiveMessages(array_map(
            fn (int $offset) => new ConsumedMessage('orders', 0, [], ['offset' => $offset], null, $offset, null),
            range(0, $messages - 1),
        ));

        $this->app->instance(FakeKafkaConsumer::class, $consumer = new FakeKafkaConsumer);

        return $consumer;
    }
}
