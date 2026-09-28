<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Producers;

use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;

final class PendingMessageTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_keeps_changes_made_before_replacing_the_message(): void
    {
        $message = Kafka::publish('orders')
            ->withKey('order-1')
            ->withHeader('source', 'api')
            ->withMessage(Message::create()->withBody(['id' => 1]))
            ->getMessage();

        $this->assertSame('order-1', $message->getKey());
        $this->assertSame(['source' => 'api'], $message->toArray()['headers']);
        $this->assertSame(['id' => 1], $message->getBody());
    }

    #[Test]
    public function it_applies_the_changes_in_the_order_they_were_made(): void
    {
        $message = Kafka::publish('orders')
            ->withBodyKey('id', 1)
            ->withBody(['id' => 2])
            ->withBodyKey('status', 'paid')
            ->getMessage();

        $this->assertSame(['id' => 2, 'status' => 'paid'], $message->getBody());
    }

    #[Test]
    public function it_does_not_modify_the_given_message(): void
    {
        $original = Message::create()->withBody(['id' => 1]);

        Kafka::fake();

        Kafka::publish('orders')->withMessage($original)->withKey('order-1')->send();

        $this->assertNull($original->getTopicName());
        $this->assertNull($original->getKey());
        Kafka::assertPublishedOn('orders', callback: fn (Message $message) => $message->getKey() === 'order-1');
    }

    #[Test]
    public function the_topic_given_to_publish_is_only_used_when_the_message_has_no_topic(): void
    {
        $this->assertSame('payments', Kafka::publish('orders')->withMessage(Message::create('payments'))->getMessage()->getTopicName());
        $this->assertSame('orders', Kafka::publish('orders')->withMessage(Message::create())->getMessage()->getTopicName());
    }

    #[Test]
    public function on_topic_overrides_the_topic_of_the_message_in_any_order(): void
    {
        $this->assertSame('refunds', Kafka::publish()->onTopic('refunds')->withMessage(Message::create('payments'))->getMessage()->getTopicName());
        $this->assertSame('refunds', Kafka::publish()->withMessage(Message::create('payments'))->onTopic('refunds')->getMessage()->getTopicName());
    }
}
