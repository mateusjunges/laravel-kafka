<?php

namespace Junges\Kafka\Tests\Consumers;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Tests\Fakes\FakeConsumer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class ConsumerBuilderTest extends LaravelKafkaTestCase
{
    public function testItReturnsAConsumerInstance()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')->build();

        $this->assertInstanceOf(Consumer::class, $consumer);
    }

    public function testItCanSubscribeToATopic()
    {
        $consumer = ConsumerBuilder::create('broker');

        $consumer->subscribe('test-topic');

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['test-topic'], $topics);
    }

    public function testItThrowsInvalidArgumentExceptionIfCreatingWithInvalidTopic()
    {
        $this->expectException(InvalidArgumentException::class);

        ConsumerBuilder::create('broker', [1234], 'group');
    }

    public function testItCanSaveTheCommitBatchSize()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')
            ->withCommitBatchSize(1);

        $commitValue = $this->getPropertyWithReflection('commit', $consumer);

        $this->assertEquals(1, $commitValue);
    }

    public function testItUsesTheCorrectHandler()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')
            ->withHandler(new FakeConsumer());

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $handler = $this->getPropertyWithReflection('handler', $consumer);

        $this->assertInstanceOf(Closure::class, $handler);
    }

    public function testItCanSetMaxMessages()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')
            ->withMaxMessages(2);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $maxMessages = $this->getPropertyWithReflection('maxMessages', $consumer);

        $this->assertEquals(2, $maxMessages);
    }

    public function testItCanSetMaxCommitRetries()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')
            ->withMaxCommitRetries(2);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $maxCommitRetries = $this->getPropertyWithReflection('maxCommitRetries', $consumer);

        $this->assertEquals(2, $maxCommitRetries);
    }

    public function testItCanSetTheDeadLetterQueue()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')
            ->withDlq('test-topic-dlq');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $dlq = $this->getPropertyWithReflection('dlq', $consumer);

        $this->assertEquals('test-topic-dlq', $dlq);
    }

    public function testItUsesDlqSuffixIfDlqIsNull()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')
            ->withDlq();

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $dlq = $this->getPropertyWithReflection('dlq', $consumer);

        $this->assertEquals('test-topic-dlq', $dlq);
    }

    public function testItCanSetSasl()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')
            ->withSasl($sasl = new Sasl(
                'username',
                'password',
                'mechanisms'
            ));

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $saslConfig = $this->getPropertyWithReflection('saslConfig', $consumer);

        $this->assertEquals($sasl, $saslConfig);
    }

    public function testItCanAddMiddlewaresToTheHandler()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')
            ->withMiddleware(function ($message, callable $next) {
                $next($message);
            });

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $middlewares = $this->getPropertyWithReflection('middlewares', $consumer);

        $this->assertIsArray($middlewares);

        $this->assertIsCallable($middlewares[0]);
    }

    public function testItCanSetSecurityProtocol()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')
            ->withSecurityProtocol('security');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $securityProtocol = $this->getPropertyWithReflection('securityProtocol', $consumer);

        $this->assertEquals('security', $securityProtocol);
    }

    public function testItCanSetAutoCommit()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')
            ->withAutoCommit();

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $autoCommit = $this->getPropertyWithReflection('autoCommit', $consumer);

        $this->assertTrue($autoCommit);
    }

    public function testItCanSetConsumerOptions()
    {
        $consumer = ConsumerBuilder::create('broker', ['test-topic'], 'group')
            ->withOptions([
                'auto.offset.reset' => 'latest',
                'enable.auto.commit' => 'false',
            ]);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $options = $this->getPropertyWithReflection('options', $consumer);

        $this->assertIsArray($options);
        $this->assertArrayHasKey('auto.offset.reset', $options);
        $this->assertArrayHasKey('enable.auto.commit', $options);
        $this->assertEquals('latest', $options['auto.offset.reset']);
        $this->assertEquals('false', $options['enable.auto.commit']);
    }
}
