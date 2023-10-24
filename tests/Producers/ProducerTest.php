<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Producers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Message\Serializers\JsonSerializer;
use Junges\Kafka\Producers\Producer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

final class ProducerTest extends LaravelKafkaTestCase
{
    public function testItDoesNotDoubleSerializeMessageWhenUsingJsonSerializer(): void
    {
        $this->mockKafkaProducer();
        $producer = new Producer(new Config('broker', ['test-topic']), new JsonSerializer());
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
