<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Producers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Message\Serializers\JsonSerializer;
use Junges\Kafka\Producers\Producer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;

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
    public function it_calls_callback_after_flushing_messages(): void
    {
        $this->mockKafkaProducer();
        $callbackCalls = 0;
        $receivedMessages = [];

        $producer = new Producer(
            new Config('broker', ['test-topic']),
            new JsonSerializer,
            false,
            function (array $messages) use (&$callbackCalls, &$receivedMessages) {
                $callbackCalls++;
                $receivedMessages = $messages;
            }
        );

        $message = new Message(
            body: ['key' => 'value'],
        );
        $message->onTopic('test-topic');

        $producer->produce($message);

        $this->assertSame(1, $callbackCalls);
        $this->assertCount(1, $receivedMessages);
        $this->assertInstanceOf(ProducerMessage::class, $receivedMessages[0]);
        $this->assertSame('test-topic', $receivedMessages[0]->getTopicName());
        $this->assertSame(['key' => 'value'], json_decode((string) $receivedMessages[0]->getBody(), true));
    }
}
