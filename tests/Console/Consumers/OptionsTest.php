<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Console\Consumers;

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
            'brokers' => config('kafka.brokers'),
            'groupId' => config('kafka.group_id'),
            'securityProtocol' => config('kafka.securityProtocol'),
            'sasl' => [
                'mechanisms' => config('kafka.sasl.mechanisms'),
                'username' => config('kafka.sasl.username'),
                'password' => config('kafka.sasl.password'),
            ],
        ];
    }

    public function testItInstantiateTheClassWithCorrectOptions(): void
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
        $this->assertEquals(FakeHandler::class, $options->getConsumer());
        $this->assertEquals('test', $options->getGroupId());
        $this->assertEquals(1, $options->getCommit());
        $this->assertEquals('test-dlq', $options->getDlq());
        $this->assertEquals(2, $options->getMaxMessages());
        $this->assertEquals('plaintext', $options->getSecurityProtocol());
        $this->assertNull($options->getSasl());
    }

    public function testItInstantiatesUsingOnlyRequiredOptions(): void
    {
        $options = [
            'topics' => 'test-topic,test-topic-1',
            'consumer' => FakeHandler::class,
        ];

        $options = new Options($options, $this->config);

        $this->assertEquals('localhost:9092', $options->getBroker());
        $this->assertEquals(['test-topic', 'test-topic-1'], $options->getTopics());
        $this->assertEquals(FakeHandler::class, $options->getConsumer());
        $this->assertNull($options->getGroupId());
        $this->assertEquals(1, $options->getCommit());
        $this->assertNull($options->getDlq());
        $this->assertEquals(-1, $options->getMaxMessages());
        $this->assertEquals('plaintext', $options->getSecurityProtocol());
        $this->assertNull($options->getSasl());
    }
}
