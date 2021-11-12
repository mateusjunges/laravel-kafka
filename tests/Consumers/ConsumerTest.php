<?php

namespace Junges\Kafka\Tests\Consumers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Exceptions\KafkaConsumerException;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\Fakes\FakeConsumer;
use Junges\Kafka\Tests\Fakes\FakeHandler;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\Message;

class ConsumerTest extends LaravelKafkaTestCase
{
    public function testItConsumesAMessageSuccessfullyAndCommit()
    {
        $fakeHandler = new FakeHandler();

        $message = new Message();
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = '{"body": "message payload"}';

        $this->mockConsumerWithMessage($message);

        $this->mockProducer();

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            sasl: null,
            dlq: null,
            maxMessages: 1,
            maxCommitRetries: 1
        );

        $consumer = new Consumer($config, new JsonDeserializer());
        $consumer->consume();

        $this->assertInstanceOf(ConsumedMessage::class, $fakeHandler->lastMessage());
    }

    public function testItCanConsumeMessages()
    {
        $message = new Message();
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test';
        $message->payload = '{"body": "message payload"}';

        $this->mockConsumerWithMessage($message);

        $this->mockProducer();

        $consumer = Kafka::createConsumer(['test'])
            ->withHandler($fakeConsumer = new FakeConsumer())
            ->withAutoCommit()
            ->withMaxMessages(1)
            ->build();

        $consumer->consume();

        $this->assertInstanceOf(ConsumedMessage::class, $fakeConsumer->getMessage());
    }

    public function testConsumeMessageWithError()
    {
        $this->mockProducer();

        $this->expectException(KafkaConsumerException::class);

        $fakeHandler = new FakeHandler();

        $config = new Config(
            broker: 'broker',
            topics: ['test-topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $fakeHandler,
            sasl: null,
            dlq: null,
            maxMessages: 1,
            maxCommitRetries: 1
        );

        $message = new Message();
        $message->err = 1;
        $message->topic_name = 'test-topic';

        $this->mockConsumerWithMessageFailingCommit($message);

        $consumer = new Consumer($config, new JsonDeserializer());
        $consumer->consume();
    }
}
