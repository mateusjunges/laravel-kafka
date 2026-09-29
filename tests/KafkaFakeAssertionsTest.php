<?php declare(strict_types=1);

namespace Junges\Kafka\Tests;

use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Message\Message;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Test;

final class KafkaFakeAssertionsTest extends LaravelKafkaTestCase
{
    #[Test]
    public function assertions_accept_a_callback_as_the_expected_message(): void
    {
        Kafka::fake();

        Kafka::publish('orders')->withBody(['id' => 1])->send();

        Kafka::assertPublished(fn (ProducerMessage $message) => $message->getBody() === ['id' => 1]);
        Kafka::assertPublishedOn('orders', fn (ProducerMessage $message) => $message->getBody() === ['id' => 1]);
        Kafka::assertPublishedTimes(1, fn (ProducerMessage $message) => $message->getTopicName() === 'orders');
        Kafka::assertPublishedOnTimes('orders', 1, fn (ProducerMessage $message) => $message->getBody()['id'] === 1);

        $this->assertFails(fn () => Kafka::assertPublished(fn (ProducerMessage $message) => $message->getBody() === ['id' => 2]));
    }

    #[Test]
    public function the_expected_message_and_the_callback_must_both_match(): void
    {
        Kafka::fake();

        Kafka::publish('orders')->withKey('order-1')->withBody(['id' => 1])->send();

        $expected = new Message(topicName: 'orders', body: ['id' => 1], key: 'order-1');

        Kafka::assertPublished($expected, fn (ProducerMessage $message) => $message->getKey() === 'order-1');

        $this->assertFails(fn () => Kafka::assertPublished($expected, fn (ProducerMessage $message) => $message->getKey() === 'order-2'));
        $this->assertFails(fn () => Kafka::assertPublished(new Message(topicName: 'orders', body: ['id' => 2], key: 'order-1'), fn () => true));
    }

    #[Test]
    public function it_asserts_a_message_was_not_published(): void
    {
        Kafka::fake();

        Kafka::publish('orders')->withBody(['id' => 1])->send();

        Kafka::assertNotPublished(fn (ProducerMessage $message) => $message->getBody() === ['id' => 2]);
        Kafka::assertNotPublished(new Message(topicName: 'orders', body: ['id' => 2]));

        $this->assertFails(fn () => Kafka::assertNotPublished(fn (ProducerMessage $message) => $message->getBody() === ['id' => 1]));
    }

    #[Test]
    public function it_asserts_nothing_was_published_on_a_topic(): void
    {
        Kafka::fake();

        Kafka::publish('orders')->withBody(['id' => 1])->send();

        Kafka::assertNothingPublishedOn('payments');

        $this->assertFails(fn () => Kafka::assertNothingPublishedOn('orders'));
    }

    #[Test]
    public function consumed_messages_can_be_created_with_only_the_relevant_properties(): void
    {
        $message = new ConsumedMessage(topicName: 'orders', body: ['id' => 1]);

        $this->assertSame('orders', $message->getTopicName());
        $this->assertSame(['id' => 1], $message->getBody());
        $this->assertSame(0, $message->getPartition());
        $this->assertSame(0, $message->getOffset());
        $this->assertSame([], $message->getHeaders());
        $this->assertNull($message->getKey());
    }

    private function assertFails(callable $assertion): void
    {
        try {
            $assertion();
        } catch (AssertionFailedError) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('The assertion should have failed.');
    }
}
