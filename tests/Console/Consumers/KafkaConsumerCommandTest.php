<?php

namespace Console\Consumers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\Fakes\FakeHandler;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\Message;

class KafkaConsumerCommandTest extends LaravelKafkaTestCase
{
    public function testItCanConsumeMessages()
    {
        $this->mockProducer();

        $fakeHandler = new FakeHandler();

        $message = new Message();
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';
        $message->offset = 0;
        $message->partition = 1;
        $message->headers = [];

        $this->mockConsumerWithMessage($message);

        $config = new Config(
            'broker',
            ['test-topic'],
            'security',
            1,
            'group',
            $fakeHandler,
            null,
            null,
            1,
            1
        );

        $consumer = new Consumer($config, new JsonDeserializer());

        $this->app->bind(Consumer::class, function () use ($consumer) {
            return $consumer;
        });

        $this->artisan("kafka:consume --topics=test-topic --consumer=\\\\Junges\\\\Kafka\\\\Tests\\\\Fakes\\\\FakeHandler");

        $this->assertInstanceOf(ConsumedMessage::class, $fakeHandler->lastMessage());
    }
}
