<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Producers;

use Illuminate\Support\Facades\Event;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Events\MessageDeliveryFailed;
use Junges\Kafka\Events\MessagePublished;
use Junges\Kafka\Events\PublishingMessage;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Message\Serializers\JsonSerializer;
use Junges\Kafka\Producers\Producer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\Message as RdKafkaMessage;
use RdKafka\Producer as KafkaProducer;
use RdKafka\ProducerTopic;
use ReflectionMethod;
use ReflectionProperty;

final class ProducerTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_does_not_double_serialize_message_when_using_json_serializer(): void
    {
        $this->mockKafkaProducer();
        $producer = new Producer(new Config('broker', ['test-topic']), new JsonSerializer);
        $payload = ['key' => 'value'];

        $message = new Message(
            body: $payload,
        );
        $message->onTopic('test-topic');
        $producer->produce($message);
        $producer->produce($message);

        $this->assertSame($payload, $message->getBody());
    }

    #[Test]
    public function it_does_not_leak_pending_messages_when_no_flush_callback_is_defined(): void
    {
        $this->mockKafkaProducer();

        $producer = new Producer(
            new Config('broker', ['test-topic']),
            new JsonSerializer,
        );

        $message = new Message(body: ['key' => 'value']);
        $message->onTopic('test-topic');

        $producer->produce($message);
        $producer->produce($message);
        $producer->produce($message);

        // Reflect on pendingMessages to assert it has been cleared after each flush
        $reflection = new ReflectionProperty(Producer::class, 'pendingMessages');
        $reflection->setAccessible(true);

        $this->assertSame([], $reflection->getValue($producer));
    }

    #[Test]
    public function it_calls_callback_after_flushing_messages(): void
    {
        $this->mockKafkaProducer();
        $callbackCalls = 0;
        $receivedMessages = [];

        $producer = (new Producer(new Config('broker', ['test-topic']), new JsonSerializer))
            ->withFlushCallback(function (array $messages) use (&$callbackCalls, &$receivedMessages) {
                $callbackCalls++;
                $receivedMessages = $messages;
            });

        $message = new Message(
            body: ['key' => 'value'],
        );
        $message->onTopic('test-topic');

        $producer->produce($message);

        $this->assertSame(0, $callbackCalls);

        $producer->flush();

        $this->assertSame(1, $callbackCalls);
        $this->assertCount(1, $receivedMessages);
        $this->assertInstanceOf(ProducerMessage::class, $receivedMessages[0]);
        $this->assertSame('test-topic', $receivedMessages[0]->getTopicName());
        $this->assertSame(['key' => 'value'], json_decode((string) $receivedMessages[0]->getBody(), true));
    }

    #[Test]
    public function the_published_event_has_the_message_id_sent_to_kafka(): void
    {
        Event::fake();

        $sentHeaders = null;

        $topic = m::mock(ProducerTopic::class);
        $topic->shouldReceive('producev')->once()->withArgs(function (...$arguments) use (&$sentHeaders) {
            $sentHeaders = $arguments[4];

            return true;
        });

        $kafkaProducer = m::mock(KafkaProducer::class);
        $kafkaProducer->shouldReceive('newTopic')->andReturn($topic);
        $kafkaProducer->shouldReceive('poll');
        $kafkaProducer->shouldReceive('flush')->andReturn(RD_KAFKA_RESP_ERR_NO_ERROR);

        $this->app->bind(KafkaProducer::class, fn () => $kafkaProducer);

        $message = Message::create('test-topic')->withBody(['key' => 'value']);

        (new Producer(new Config('broker', ['test-topic']), new JsonSerializer))->produce($message);

        $id = $sentHeaders[config('kafka.message_id_key')];

        $this->assertSame($message->getMessageIdentifier(), $id);
        Event::assertDispatched(MessagePublished::class, fn (MessagePublished $event) => $event->message->getMessageIdentifier() === $id);
        Event::assertDispatched(PublishingMessage::class, fn (PublishingMessage $event) => $event->message->getMessageIdentifier() === $id);
    }

    #[Test]
    public function it_dispatches_an_event_when_a_message_could_not_be_delivered(): void
    {
        Event::fake();
        $this->mockKafkaProducer();

        $failed = new RdKafkaMessage;
        $failed->err = RD_KAFKA_RESP_ERR__MSG_TIMED_OUT;
        $failed->topic_name = 'orders';
        $failed->partition = 2;
        $failed->key = 'order-1';
        $failed->payload = '{"id":1}';
        $failed->headers = [];
        $failed->opaque = 'message-id';

        $this->reportDelivery(new Producer(new Config('broker', ['orders']), new JsonSerializer), $failed);

        Event::assertDispatched(MessageDeliveryFailed::class, fn (MessageDeliveryFailed $event) => $event->topic === 'orders'
            && $event->partition === 2
            && $event->key === 'order-1'
            && $event->payload === '{"id":1}'
            && $event->errorCode === RD_KAFKA_RESP_ERR__MSG_TIMED_OUT
            && $event->error === rd_kafka_err2str(RD_KAFKA_RESP_ERR__MSG_TIMED_OUT)
            && $event->getMessageIdentifier() === 'message-id');
    }

    #[Test]
    public function it_does_not_dispatch_an_event_for_delivered_messages(): void
    {
        Event::fake();
        $this->mockKafkaProducer();

        $delivered = new RdKafkaMessage;
        $delivered->err = RD_KAFKA_RESP_ERR_NO_ERROR;

        $this->reportDelivery(new Producer(new Config('broker', ['orders']), new JsonSerializer), $delivered);

        Event::assertNotDispatched(MessageDeliveryFailed::class);
    }

    private function reportDelivery(Producer $producer, RdKafkaMessage $message): void
    {
        (new ReflectionMethod($producer, 'handleDeliveryReport'))->invoke($producer, $message);
    }
}
