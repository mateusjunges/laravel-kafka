<?php

namespace Integration;

use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Tests\Integration\TestConsumer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class IntegrationTest extends LaravelKafkaTestCase
{
    public function testSuccess(): void
    {
        $topicName = 'php-kafka-consumer-topic';
        $this->sendMessage($topicName, 'Groundhog day');

        $this->consumeMessages(1, $topicName, new TestConsumer());

        $this->assertSame(1, count(TestConsumer::$messages));
        $this->assertContains('Groundhog day', TestConsumer::$messages);
    }

    public function testDlq(): void
    {
        $topicName = 'php-kafka-consumer-topic';
        $this->sendMessage($topicName, 'Cest la vie');

        $this->consumeMessages(
            1,
            $topicName,
            new TestConsumer([TestConsumer::RESPONSE_ERROR, TestConsumer::RESPONSE_ERROR])
        );

        $this->assertEmpty(TestConsumer::$messages);

        $this->consumeMessages(1, $topicName . '-dlq', new TestConsumer());

        $this->assertSame(1, count(TestConsumer::$messages));
        $this->assertContains('Cest la vie', TestConsumer::$messages);
    }

    public function testMultipleMessages()
    {
        $topicName = 'php-kafka-consumer-topic';
        $this->sendMessage($topicName, 'You are my fire');
        $this->sendMessage($topicName, 'The one desire');
        $this->sendMessage($topicName, 'Believe when I say');
        $this->sendMessage($topicName, 'I want it that way');

        $this->consumeMessages(4, $topicName, new TestConsumer());

        $this->assertSame(4, count(TestConsumer::$messages));
        $this->assertContains('You are my fire', TestConsumer::$messages);
        $this->assertContains('The one desire', TestConsumer::$messages);
        $this->assertContains('Believe when I say', TestConsumer::$messages);
        $this->assertContains('I want it that way', TestConsumer::$messages);
    }

    private function sendMessage(string $topicName, string $msg)
    {
        $rdKafkaConf = new \RdKafka\Conf();
        $rdKafkaConf->set('log_level', (string) LOG_DEBUG);
        $rdKafkaConf->set('debug', 'all');
        $rdKafkaConf->set('security.protocol', 'PLAINTEXT');
        $rdKafkaConf->set('sasl.mechanisms', 'PLAIN');
        $rdKafkaConf->set('bootstrap.servers', env('KAFKA_BROKERS'));

        $producer = new \RdKafka\Producer($rdKafkaConf);
        $producer->addBrokers(env('KAFKA_BROKERS'));

        $topic = $producer->newTopic($topicName);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $msg);

        if (method_exists($producer, 'flush')) {
            $producer->flush(12000);
        }
    }

    private function consumeMessages(int $numberOfMessages, string $topicName, callable $handler): void
    {
        $consumer = ConsumerBuilder::create(env('KAFKA_BROKERS'), [$topicName], 'test-group-id')
            ->withCommitBatchSize(1)
            ->withMaxCommitRetries(6)
            ->withHandler($handler)
            ->withDlq($topicName . '-dlq')
            ->withMaxMessages($numberOfMessages)
            ->build();

        $consumer->consume();
    }
}
