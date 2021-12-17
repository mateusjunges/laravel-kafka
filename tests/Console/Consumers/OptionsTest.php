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
            'broker' => config('kafka.brokers'),
            'groupId' => config('kafka.group_id'),
            'securityProtocol' => config('kafka.securityProtocol'),
            'sasl' => [
                'mechanisms' => config('kafka.sasl.mechanisms'),
                'username' => config('kafka.sasl.username'),
                'password' => config('kafka.sasl.password'),
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
            'securityProtocol' => 'plaintext'
        ];

        $options = new Options($commandLineOptions, $this->config);

        $this->assertEquals(['test-topic', 'test-topic-1'], $this->getPropertyWithReflection('topics', $options));
        $this->assertEquals('test', $this->getPropertyWithReflection('groupId', $options));
        $this->assertEquals(1, $this->getPropertyWithReflection('commit', $options));
        $this->assertEquals('test-dlq', $this->getPropertyWithReflection('dlq', $options));
        $this->assertEquals(2, $this->getPropertyWithReflection('maxMessages', $options));
        $this->assertEquals('plaintext', $this->getPropertyWithReflection('securityProtocol', $options));
        $this->assertEquals(null, $options->getSasl());
    }

    public function testItInstantiatesUsingOnlyRequiredOptions()
    {
        $options = [
            'topics' => 'test-topic,test-topic-1',
            'consumer' => FakeHandler::class,
        ];

        $options = new Options($options, $this->config);


        $this->assertEquals(['test-topic', 'test-topic-1'], $this->getPropertyWithReflection('topics', $options));
        $this->assertNull($this->getPropertyWithReflection('groupId', $options));
        $this->assertEquals(1, $this->getPropertyWithReflection('commit', $options));
        $this->assertNull($this->getPropertyWithReflection('dlq', $options));
        $this->assertEquals(-1, $this->getPropertyWithReflection('maxMessages', $options));
        $this->assertEquals('plaintext', $this->getPropertyWithReflection('securityProtocol', $options));
        $this->assertEquals(null, $options->getSasl());
    }
}
