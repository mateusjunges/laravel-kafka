<?php

namespace Junges\Kafka\Tests\Producers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Message\Serializers\JsonSerializer;
use Junges\Kafka\Producers\Producer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class ProducerTest extends LaravelKafkaTestCase
{
    public function testItDoesNotDoubleSerializeMessageWhenUsingJsonSerializer()
    {
        $this->mockKafkaProducer();
        $producer = new Producer(new Config('broker', ['test-topic']), 'test-topic', new JsonSerializer());
        $payload = ['key' => 'value'];

        $message = new Message(
            body: $payload,
        );
        $producer->produce($message);
        $producer->produce($message);

        $this->assertSame($payload, $message->getBody());
    }
}
