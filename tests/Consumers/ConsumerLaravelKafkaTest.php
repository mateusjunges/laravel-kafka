<?php

namespace Junges\Kafka\Tests\Consumers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Exceptions\KafkaConsumerException;
use Junges\Kafka\Tests\Fakes\FakeHandler;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\Message;

class ConsumerLaravelKafkaTest extends LaravelKafkaTestCase
{
    public function testItConsumesAMessageSuccessfullyAndCommit()
    {
        $fakeHandler = new FakeHandler();

        $message = new Message();
        $message->err = 0;
        $message->key = 'key';
        $message->topic_name = 'test-topic';
        $message->payload = 'message payload';

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

        $consumer = new Consumer($config);
        $consumer->consume();

        $this->assertEquals($message, $fakeHandler->lastMessage());
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

        $consumer = new Consumer($config);
        $consumer->consume();
    }
}
