<?php

namespace Junges\Kafka\Tests\Consumers;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Tests\Fakes\FakeConsumer;
use Junges\Kafka\Tests\TestCase;

class ConsumerBuilderTest extends TestCase
{
    public function testItReturnsAConsumerInstance()
    {
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])->build();

        $this->assertInstanceOf(Consumer::class, $consumer);
    }

    public function testItThrowsInvalidArgumentExceptionIfCreatingWithInvalidTopic()
    {
        $this->expectException(InvalidArgumentException::class);

        ConsumerBuilder::create('broker', 'group', [1234])->build();
    }

    public function testItCanSaveTheCommitBatchSize()
    {
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])
            ->withCommitBatchSize(1);

        $commitValue = $this->getPropertyWithReflection('commit', $consumer);

        $this->assertEquals(1, $commitValue);
    }

    public function testItUsesTheCorrectHandler()
    {
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])
            ->withHandler(new FakeConsumer());

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $handler = $this->getPropertyWithReflection('handler', $consumer);

        $this->assertInstanceOf(Closure::class, $handler);
    }

    public function testItCanSetMaxMessages()
    {
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])
            ->withMaxMessages(2);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $maxMessages = $this->getPropertyWithReflection('maxMessages', $consumer);

        $this->assertEquals(2, $maxMessages);
    }

    public function testItCanSetMaxCommitRetries()
    {
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])
            ->withMaxCommitRetries(2);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $maxCommitRetries = $this->getPropertyWithReflection('maxCommitRetries', $consumer);

        $this->assertEquals(2, $maxCommitRetries);
    }

    public function testItCanSetTheDeadLetterQueue()
    {
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])
            ->withDlq('test-topic-dlq');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $dlq = $this->getPropertyWithReflection('dlq', $consumer);

        $this->assertEquals('test-topic-dlq', $dlq);
    }

    public function testItUsesDlqSuffixIfDlqIsNull()
    {
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])
            ->withDlq();

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $dlq = $this->getPropertyWithReflection('dlq', $consumer);

        $this->assertEquals('test-topic-dlq', $dlq);
    }

    public function testItCanSetSasl()
    {
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])
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
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])
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
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])
            ->withSecurityProtocol('security');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $securityProtocol = $this->getPropertyWithReflection('securityProtocol', $consumer);

        $this->assertEquals('security', $securityProtocol);
    }

    public function testItCanSetAutoCommit()
    {
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])
            ->withAutoCommit();

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $autoCommit = $this->getPropertyWithReflection('autoCommit', $consumer);

        $this->assertTrue($autoCommit);
    }

    public function testItCanSetConsumerOptions()
    {
        $consumer = ConsumerBuilder::create('broker', 'group', ['test-topic'])
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
