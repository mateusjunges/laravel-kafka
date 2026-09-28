<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Commit\VoidCommitter;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Config\RebalanceStrategy;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Consumers\Consumer;
use Junges\Kafka\Contracts\Committer;
use Junges\Kafka\Contracts\CommitterFactory;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\Fakes\FakeConsumer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use LogicException;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\TopicPartition;

final class ConsumerBuilderTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_returns_a_consumer_instance(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->build();

        $this->assertInstanceOf(Consumer::class, $consumer);
    }

    #[Test]
    public function it_can_subscribe_to_a_topic(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'));

        $consumer->subscribe('foo');

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo'], $topics);
    }

    #[Test]
    public function it_does_not_subscribe_to_a_topic_twice(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'));

        $consumer->subscribe('foo', 'foo');

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo'], $topics);
    }

    #[Test]
    public function i_can_change_deserializers_on_the_fly(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'));

        $consumer->usingDeserializer(new JsonDeserializer);

        $deserializer = $this->getPropertyWithReflection('deserializer', $consumer);

        $this->assertInstanceOf(JsonDeserializer::class, $deserializer);
    }

    #[Test]
    public function it_can_subscribe_to_more_than_one_topics_at_once(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'));

        $consumer->subscribe('foo', 'bar');

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo', 'bar'], $topics);

        $consumer = Builder::create(new ConnectionConfig('default', 'broker'));

        $consumer->subscribe(['foo', 'bar']);

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo', 'bar'], $topics);
    }

    #[Test]
    public function it_can_set_consumer_group_id(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->withGroupId('foo');

        $groupId = $this->getPropertyWithReflection('groupId', $consumer);

        $this->assertEquals('foo', $groupId);
    }

    #[Test]
    public function it_throws_invalid_argument_exception_if_creating_with_invalid_topic(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Builder::create(new ConnectionConfig('default', 'broker'), [1234], 'group');
    }

    #[Test]
    public function it_uses_the_correct_handler(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->withHandler(new FakeConsumer);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $handler = $this->getPropertyWithReflection('handler', $consumer);

        $this->assertInstanceOf(Closure::class, $handler);
    }

    #[Test]
    public function it_can_set_max_messages(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->stopAfterMessages(2);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $maxMessages = $this->getPropertyWithReflection('maxMessages', $consumer);

        $this->assertEquals(2, $maxMessages);
    }

    #[Test]
    public function it_can_set_the_dead_letter_queue(): void
    {
        $builder = Builder::create(new ConnectionConfig('default', 'broker'))->subscribe('test')->withDlq('test-topic-dlq');

        $this->assertSame('test-topic-dlq', $this->builtConfig($builder)->getDlq());
    }

    #[Test]
    public function it_names_the_dead_letter_queue_after_the_first_topic_when_no_name_is_given(): void
    {
        $builder = Builder::create(new ConnectionConfig('default', 'broker'), ['foo'])->withDlq();

        $this->assertSame('foo-dlq', $this->builtConfig($builder)->getDlq());
    }

    #[Test]
    public function it_names_the_dead_letter_queue_after_topics_subscribed_after_calling_with_dlq(): void
    {
        $builder = Builder::create(new ConnectionConfig('default', 'broker'))->withDlq()->subscribe('orders');

        $this->assertSame('orders-dlq', $this->builtConfig($builder)->getDlq());
    }

    #[Test]
    public function it_names_the_dead_letter_queue_after_the_assigned_partitions_when_not_subscribing(): void
    {
        $builder = Builder::create(new ConnectionConfig('default', 'broker'))
            ->withDlq()
            ->assignPartitions([new TopicPartition('payments', 0)]);

        $this->assertSame('payments-dlq', $this->builtConfig($builder)->getDlq());
    }

    #[Test]
    public function it_cant_build_a_consumer_with_an_unnamed_dlq_without_any_topics(): void
    {
        $builder = Builder::create(new ConnectionConfig('default', 'broker'))->withDlq();

        $this->expectException(ConsumerException::class);

        $builder->build();
    }

    #[Test]
    public function it_combines_the_partitions_assigned_callback_and_the_offset_resolver_in_any_order(): void
    {
        $partitions = [new TopicPartition('test-topic', 0)];
        $withOffsets = [new TopicPartition('test-topic', 0, 42)];

        foreach ([true, false] as $offsetsFirst) {
            $notified = null;

            $builder = Builder::create(new ConnectionConfig('default', 'broker'), ['test-topic'], 'group');
            $onAssign = function (array $assigned) use (&$notified) {
                $notified = $assigned;
            };
            $offsetResolver = fn (array $assigned) => $withOffsets;

            if ($offsetsFirst) {
                $builder->resolveOffsetsUsing($offsetResolver)->onPartitionsAssigned($onAssign);
            } else {
                $builder->onPartitionsAssigned($onAssign)->resolveOffsetsUsing($offsetResolver);
            }

            $rebalance = $this->builtConfig($builder)->getConfigCallbacks()['setRebalanceCb'];

            $kafkaConsumer = m::mock(KafkaConsumer::class);
            $kafkaConsumer->shouldReceive('assign')->once()->with($withOffsets);
            $kafkaConsumer->shouldReceive('assign')->once()->with(null);

            $rebalance($kafkaConsumer, RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS, $partitions);
            $rebalance($kafkaConsumer, RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS, $partitions);

            $this->assertSame($withOffsets, $notified);
        }
    }

    #[Test]
    public function it_does_not_combine_a_rebalance_callback_with_the_partitions_assigned_callback(): void
    {
        $builder = Builder::create(new ConnectionConfig('default', 'broker'), ['test-topic'])
            ->onPartitionsAssigned(fn () => null)
            ->onRebalance(fn () => null);

        $this->expectException(LogicException::class);

        $builder->build();
    }

    #[Test]
    public function it_keeps_a_rebalance_callback_when_no_partitions_assigned_callback_is_set(): void
    {
        $builder = Builder::create(new ConnectionConfig('default', 'broker'), ['test-topic'])
            ->onRebalance($callback = fn () => null);

        $this->assertSame($callback, $this->builtConfig($builder)->getConfigCallbacks()['setRebalanceCb']);
    }

    #[Test]
    public function it_can_set_sasl(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))
            ->withSasl('username', 'password', 'mechanisms');

        $expectedSaslConfig = new Sasl('username', 'password', 'mechanisms');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $saslConfig = $this->getPropertyWithReflection('saslConfig', $consumer);

        $this->assertEquals($expectedSaslConfig, $saslConfig);
    }

    #[Test]
    public function it_can_add_middlewares_to_the_handler(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'), ['foo'], 'group')
            ->withMiddleware(function ($message, callable $next) {
                $next($message);
            });

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $middlewares = $this->getPropertyWithReflection('middlewares', $consumer);

        $this->assertIsArray($middlewares);

        $this->assertIsCallable($middlewares[0]);
    }

    #[Test]
    public function it_can_add_invokable_classes_as_middleware(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'), ['foo'], 'group')
            ->withMiddleware(new TestMiddleware);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $middlewares = $this->getPropertyWithReflection('middlewares', $consumer);

        $this->assertIsArray($middlewares);

        $this->assertIsCallable($middlewares[0]);
    }

    #[Test]
    public function it_can_set_security_protocol(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'), ['foo'], 'group')
            ->withSecurityProtocol('security');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $securityProtocol = $this->getPropertyWithReflection('securityProtocol', $consumer);

        $this->assertEquals('security', $securityProtocol);
    }

    #[Test]
    public function it_can_set_security_protocol_via_sasl_config(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'), ['foo'], 'group')
            ->withSasl(
                'username',
                'password',
                'mechanisms',
                'protocol'
            );

        $consummerBuilt = $consumer->build();
        $this->assertInstanceOf(Consumer::class, $consummerBuilt);

        $consumerConfig = $this->getPropertyWithReflection('config', $consummerBuilt);
        $securityProtocol = $this->getPropertyWithReflection('securityProtocol', $consumerConfig);

        $this->assertEquals('protocol', $securityProtocol);
    }

    #[Test]
    public function it_can_set_auto_commit(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->withAutoCommit();

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $autoCommit = $this->getPropertyWithReflection('autoCommit', $consumer);

        $this->assertTrue($autoCommit);

        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->withAutoCommit(false);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $autoCommit = $this->getPropertyWithReflection('autoCommit', $consumer);

        $this->assertFalse($autoCommit);
    }

    #[Test]
    public function it_can_set_stop_after_last_message(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->stopWhenEmpty();

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $autoCommit = $this->getPropertyWithReflection('stopWhenEmpty', $consumer);

        $this->assertTrue($autoCommit);

        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->stopWhenEmpty(false);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $autoCommit = $this->getPropertyWithReflection('stopWhenEmpty', $consumer);

        $this->assertFalse($autoCommit);
    }

    #[Test]
    public function it_can_skip_failed_messages(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->skipFailedMessages();

        $this->assertInstanceOf(Consumer::class, $consumer->build());
        $this->assertTrue($this->getPropertyWithReflection('skipFailedMessages', $consumer));

        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->skipFailedMessages(false);

        $this->assertInstanceOf(Consumer::class, $consumer->build());
        $this->assertFalse($this->getPropertyWithReflection('skipFailedMessages', $consumer));
    }

    #[Test]
    public function it_can_set_failed_message_retries(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->retryFailedMessages(3, backoffInMs: 500);

        $this->assertInstanceOf(Consumer::class, $consumer->build());
        $this->assertSame(3, $this->getPropertyWithReflection('failedMessageRetries', $consumer));
        $this->assertSame(500, $this->getPropertyWithReflection('failedMessageRetryBackoff', $consumer));
    }

    #[Test]
    public function it_does_not_accept_negative_failed_message_retries(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Builder::create(new ConnectionConfig('default', 'broker'))->retryFailedMessages(-1);
    }

    #[Test]
    public function it_can_set_consumer_options(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))
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

    #[Test]
    public function it_can_set_rebalance_strategy_with_enum(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))
            ->withRebalanceStrategy(RebalanceStrategy::ROUND_ROBIN);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $options = $this->getPropertyWithReflection('options', $consumer);

        $this->assertIsArray($options);
        $this->assertArrayHasKey('partition.assignment.strategy', $options);
        $this->assertEquals('roundrobin', $options['partition.assignment.strategy']);
    }

    #[Test]
    public function it_can_set_rebalance_strategy_with_string(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))
            ->withRebalanceStrategy('sticky');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $options = $this->getPropertyWithReflection('options', $consumer);

        $this->assertIsArray($options);
        $this->assertArrayHasKey('partition.assignment.strategy', $options);
        $this->assertEquals('sticky', $options['partition.assignment.strategy']);
    }

    #[Test]
    public function it_throws_exception_for_invalid_rebalance_strategy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid rebalance strategy [invalid]. Valid strategies are: range, roundrobin, sticky, cooperative-sticky');

        Builder::create(new ConnectionConfig('default', 'broker'))
            ->withRebalanceStrategy('invalid');
    }

    #[Test]
    public function it_can_specify_brokers_using_with_brokers(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))->withBrokers('my-test-broker');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $brokers = $this->getPropertyWithReflection('brokers', $consumer);

        $this->assertEquals('my-test-broker', $brokers);
    }

    #[Test]
    public function it_can_build_with_custom_committer(): void
    {
        $adhocCommitterFactory = new class implements CommitterFactory
        {
            public function make(KafkaConsumer $kafkaConsumer, Config $config): Committer
            {
                return new VoidCommitter;
            }
        };
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'))
            ->usingCommitterFactory($adhocCommitterFactory)
            ->build();

        $committerFactory = $this->getPropertyWithReflection('committerFactory', $consumer);
        $this->assertInstanceOf($adhocCommitterFactory::class, $committerFactory);
    }

    #[Test]
    public function it_can_set_oauth_bearer_token_refresh_callback(): void
    {
        $consumer = Builder::create(new ConnectionConfig('default', 'broker'), ['test-topic'], 'group')
            ->onOAuthBearerTokenRefresh(function ($consumer, string $oauthConfig): void {
                // Token refresh logic
            });

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $callbacks = $this->getPropertyWithReflection('callbacks', $consumer);
        $this->assertArrayHasKey('setOauthbearerTokenRefreshCb', $callbacks);
        $this->assertIsCallable($callbacks['setOauthbearerTokenRefreshCb']);
    }

    private function builtConfig(Builder $builder): Config
    {
        return $this->getPropertyWithReflection('config', $builder->build());
    }
}

final class TestMiddleware
{
    public function __invoke(Message $message, callable $next)
    {
        return $next($message);
    }
}
