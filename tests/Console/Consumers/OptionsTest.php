<?php

namespace Console\Consumers;

use Junges\Kafka\Console\Commands\KafkaConsumer\Options;
use Junges\Kafka\Tests\Fakes\FakeHandler;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class OptionsTest extends LaravelKafkaTestCase
{
    private array $config;

    public function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'brokers' => config('kafka.consumers.default.brokers'),
            'groupId' => config('kafka.consumers.default.group_id'),
            'securityProtocol' => config('kafka.consumers.security_protocol'),
            'sasl' => [
                'mechanisms' => config('kafka.consumers.default.sasl.mechanisms'),
                'username' => config('kafka.consumers.default.sasl.username'),
                'password' => config('kafka.consumers.default.sasl.password'),
            ],
        ];
    }

    public function testItInstantiateTheClassWithCorrectOptions()
    {
        $commandLineOptions = [
            'topics' => 'test-topic,test-topic-1',
            'consumer' => FakeHandler::class,
            'groupId' => 'test',
            'commit' => 1,
            'dlq' => 'test-dlq',
            'maxMessages' => 2,
            'securityProtocol' => 'plaintext',
        ];

        $options = new Options($commandLineOptions, $this->config);

        $this->assertEquals('localhost:9092', $options->getBroker());
        $this->assertEquals(['test-topic', 'test-topic-1'], $options->getTopics());
        $this->assertEquals(FakeHandler::class, $options->getHandler());
        $this->assertEquals('test', $options->getGroupId());
        $this->assertEquals(1, $options->getCommit());
        $this->assertEquals('test-dlq', $options->getDlq());
        $this->assertEquals(2, $options->getMaxMessages());
        $this->assertEquals('plaintext', $options->getSecurityProtocol());
        $this->assertNull($options->getSasl());
    }

    public function testItInstantiatesUsingOnlyRequiredOptions()
    {
        $options = [
            'topics' => 'test-topic,test-topic-1',
            'consumer' => FakeHandler::class,
        ];

        $options = new Options($options, $this->config);

        $this->assertEquals('localhost:9092', $options->getBroker());
        $this->assertEquals(['test-topic', 'test-topic-1'], $options->getTopics());
        $this->assertEquals(FakeHandler::class, $options->getHandler());
        $this->assertEquals('default', $options->getGroupId());
        $this->assertEquals(1, $options->getCommit());
        $this->assertNull($options->getDlq());
        $this->assertEquals(-1, $options->getMaxMessages());
        $this->assertEquals('plaintext', $options->getSecurityProtocol());
        $this->assertNull($options->getSasl());
    }
}
