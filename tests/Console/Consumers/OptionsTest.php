<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Console\Consumers;

use Junges\Kafka\Console\Commands\KafkaConsumer\Options;
use Junges\Kafka\Tests\Fakes\FakeHandler;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

class OptionsTest extends LaravelKafkaTestCase
{
    private array $config;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'brokers' => 'localhost:9092',
            'groupId' => null,
            'securityProtocol' => 'PLAINTEXT',
            'sasl' => [
                'mechanisms' => null,
                'username' => null,
                'password' => null,
            ],
        ];
    }

    #[Test]
    public function it_instantiate_the_class_with_correct_options(): void
    {
        $commandLineOptions = [
            'topics' => 'test-topic,test-topic-1',
            'consumer' => FakeHandler::class,
            'groupId' => 'test',
            'dlq' => 'test-dlq',
            'maxMessages' => 2,
            'securityProtocol' => 'plaintext',
        ];

        $options = new Options($commandLineOptions, $this->config);

        $this->assertEquals('localhost:9092', $options->getBroker());
        $this->assertEquals(['test-topic', 'test-topic-1'], $options->getTopics());
        $this->assertEquals(FakeHandler::class, $options->getConsumer());
        $this->assertEquals('test', $options->getGroupId());
        $this->assertEquals('test-dlq', $options->getDlq());
        $this->assertEquals(2, $options->getMaxMessages());
        $this->assertEquals('plaintext', $options->getSecurityProtocol());
        $this->assertNull($options->getSasl());
    }

    #[Test]
    public function it_instantiates_using_only_required_options(): void
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
        $this->assertNull($options->getDlq());
        $this->assertEquals(-1, $options->getMaxMessages());
        $this->assertEquals('plaintext', $options->getSecurityProtocol());
        $this->assertNull($options->getSasl());
    }
}
