<?php

namespace Junges\Kafka\Tests\Consumers;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Message\Deserializers\NullDeserializer;
use Junges\Kafka\Tests\Fakes\FakeConsumer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use RdKafka\Message;

class ConsumerBuilderTest extends LaravelKafkaTestCase
{
    public function testItReturnsAConsumerInstance()
    {
        $consumer = ConsumerBuilder::create('broker')->build();

        $this->assertInstanceOf(Consumer::class, $consumer);
    }

    public function testItCanSubscribeToATopic()
    {
        $consumer = ConsumerBuilder::create('broker');

        $consumer->subscribe('foo');

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo'], $topics);
    }

    public function testItDoesNotSubscribeToATopicTwice()
    {
        $consumer = ConsumerBuilder::create('broker');

        $consumer->subscribe('foo', 'foo');

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo'], $topics);
    }

    public function testICanChangeDeserializersOnTheFly()
    {
        $consumer = ConsumerBuilder::create('broker');

        $consumer->usingDeserializer(new NullDeserializer());

        $deserializer = $this->getPropertyWithReflection('deserializer', $consumer);

        $this->assertInstanceOf(NullDeserializer::class, $deserializer);
    }

    public function testItCanSubscribeToMoreThanOneTopicsAtOnce()
    {
        $consumer = ConsumerBuilder::create('broker');

        $consumer->subscribe('foo', 'bar');

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo', 'bar'], $topics);

        $consumer = ConsumerBuilder::create('broker');

        $consumer->subscribe(['foo', 'bar']);

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo', 'bar'], $topics);
    }

    public function testItCanSetConsumerGroupId()
    {
        $consumer = ConsumerBuilder::create('broker')->withConsumerGroupId('foo');

        $groupId = $this->getPropertyWithReflection('groupId', $consumer);

        $this->assertEquals('foo', $groupId);
    }

    public function testItThrowsInvalidArgumentExceptionIfCreatingWithInvalidTopic()
    {
        $this->expectException(InvalidArgumentException::class);

        ConsumerBuilder::create('broker', [1234], 'group');
    }

    public function testItCanSaveTheCommitBatchSize()
    {
        $consumer = ConsumerBuilder::create('broker')
            ->withCommitBatchSize(1);

        $commitValue = $this->getPropertyWithReflection('commit', $consumer);

        $this->assertEquals(1, $commitValue);
    }

    public function testItUsesTheCorrectHandler()
    {
        $consumer = ConsumerBuilder::create('broker')->withHandler(new FakeConsumer());

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $handler = $this->getPropertyWithReflection('handler', $consumer);

        $this->assertInstanceOf(Closure::class, $handler);
    }

    public function testItCanSetMaxMessages()
    {
        $consumer = ConsumerBuilder::create('broker')->withMaxMessages(2);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $maxMessages = $this->getPropertyWithReflection('maxMessages', $consumer);

        $this->assertEquals(2, $maxMessages);
    }

    public function testItCanSetMaxCommitRetries()
    {
        $consumer = ConsumerBuilder::create('broker')->withMaxCommitRetries(2);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $maxCommitRetries = $this->getPropertyWithReflection('maxCommitRetries', $consumer);

        $this->assertEquals(2, $maxCommitRetries);
    }

    public function testItCanSetTheDeadLetterQueue()
    {
        $consumer = ConsumerBuilder::create('broker')->withDlq('test-topic-dlq');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $dlq = $this->getPropertyWithReflection('dlq', $consumer);

        $this->assertEquals('test-topic-dlq', $dlq);
    }

    public function testItUsesDlqSuffixIfDlqIsNull()
    {
        $consumer = ConsumerBuilder::create('broker', ['foo'])->withDlq();

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $dlq = $this->getPropertyWithReflection('dlq', $consumer);

        $this->assertEquals('foo-dlq', $dlq);
    }

    public function testItCanSetSasl()
    {
        $consumer = ConsumerBuilder::create('broker')
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
        $consumer = ConsumerBuilder::create('broker', ['foo'], 'group')
            ->withMiddleware(function ($message, callable $next) {
                $next($message);
            });

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $middlewares = $this->getPropertyWithReflection('middlewares', $consumer);

        $this->assertIsArray($middlewares);

        $this->assertIsCallable($middlewares[0]);
    }

    public function testItCanAddInvokableClassesAsMiddleware()
    {
        $consumer = ConsumerBuilder::create('broker', ['foo'], 'group')
            ->withMiddleware(new TestMiddleware());

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $middlewares = $this->getPropertyWithReflection('middlewares', $consumer);

        $this->assertIsArray($middlewares);

        $this->assertIsCallable($middlewares[0]);
    }

    public function testItCanSetSecurityProtocol()
    {
        $consumer = ConsumerBuilder::create('broker', ['foo'], 'group')
            ->withSecurityProtocol('security');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $securityProtocol = $this->getPropertyWithReflection('securityProtocol', $consumer);

        $this->assertEquals('security', $securityProtocol);
    }

    public function testItCanSetAutoCommit()
    {
        $consumer = ConsumerBuilder::create('broker')->withAutoCommit();

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $autoCommit = $this->getPropertyWithReflection('autoCommit', $consumer);

        $this->assertTrue($autoCommit);
    }

    public function testItCanSetConsumerOptions()
    {
        $consumer = ConsumerBuilder::create('broker')
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

class TestMiddleware
{
    public function __invoke(Message $message, callable $next)
    {
        return $next($message);
    }
}
