<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Console\Consumers;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\Fakes\FakeHandler;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\Message;

final class KafkaConsumerCommandTest extends LaravelKafkaTestCase
{
    public function testItCanConsumeMessages(): void
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

        $this->app->bind(Consumer::class, fn () => $consumer);

        $this->artisan("kafka:consume --topics=test-topic --consumer=\\\\Junges\\\\Kafka\\\\Tests\\\\Fakes\\\\FakeHandler");

        $this->assertInstanceOf(ConsumedMessage::class, $fakeHandler->lastMessage());
    }
}
