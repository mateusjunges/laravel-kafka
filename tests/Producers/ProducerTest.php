<?php

namespace Junges\Kafka\Tests\Producers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Message\Serializers\JsonSerializer;
use Junges\Kafka\Producers\MessageBatch;
use Junges\Kafka\Producers\Producer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Junges\Kafka\Message\Message;

class ProducerTest extends LaravelKafkaTestCase
{
    public function test_it_does_not_double_serialize_a_message_when_using_json_serializer()
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

    public function test_it_does_not_double_serialize_a_batch_message_when_using_json_serializer()
    {
        $this->mockKafkaProducer();
        $producer = new Producer(new Config('broker', ['test-topic']), 'test-topic', new JsonSerializer());
        $payload = ['key' => 'value'];

        $message = new Message(
            body: $payload,
        );

        $messageBatch = new MessageBatch();
        $messageBatch->push($message);

        $producer->produceBatch($messageBatch);
        $producer->produceBatch($messageBatch);

        $this->assertSame($payload, $message->getBody());
    }
}