<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Producers;

use Junges\Kafka\Config\Config;
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
}
