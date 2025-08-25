<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Closure;
use InvalidArgumentException;
use Junges\Kafka\Commit\VoidCommitter;
use Junges\Kafka\Config\Config;
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
use PHPUnit\Framework\Attributes\Test;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

final class ConsumerBuilderTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_returns_a_consumer_instance(): void
    {
        $consumer = Builder::create('broker')->build();

        $this->assertInstanceOf(Consumer::class, $consumer);
    }

    #[Test]
    public function it_can_subscribe_to_a_topic(): void
    {
        $consumer = Builder::create('broker');

        $consumer->subscribe('foo');

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo'], $topics);
    }

    #[Test]
    public function it_does_not_subscribe_to_a_topic_twice(): void
    {
        $consumer = Builder::create('broker');

        $consumer->subscribe('foo', 'foo');

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo'], $topics);
    }

    #[Test]
    public function i_can_change_deserializers_on_the_fly(): void
    {
        $consumer = Builder::create('broker');

        $consumer->usingDeserializer(new JsonDeserializer);

        $deserializer = $this->getPropertyWithReflection('deserializer', $consumer);

        $this->assertInstanceOf(JsonDeserializer::class, $deserializer);
    }

    #[Test]
    public function it_can_subscribe_to_more_than_one_topics_at_once(): void
    {
        $consumer = Builder::create('broker');

        $consumer->subscribe('foo', 'bar');

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo', 'bar'], $topics);

        $consumer = Builder::create('broker');

        $consumer->subscribe(['foo', 'bar']);

        $topics = $this->getPropertyWithReflection('topics', $consumer);

        $this->assertEquals(['foo', 'bar'], $topics);
    }

    #[Test]
    public function it_can_set_consumer_group_id(): void
    {
        $consumer = Builder::create('broker')->withConsumerGroupId('foo');

        $groupId = $this->getPropertyWithReflection('groupId', $consumer);

        $this->assertEquals('foo', $groupId);
    }

    #[Test]
    public function it_throws_invalid_argument_exception_if_creating_with_invalid_topic(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Builder::create('broker', [1234], 'group');
    }

    #[Test]
    public function it_can_save_the_commit_batch_size(): void
    {
        $consumer = Builder::create('broker')
            ->withCommitBatchSize(1);

        $commitValue = $this->getPropertyWithReflection('commit', $consumer);

        $this->assertEquals(1, $commitValue);
    }

    #[Test]
    public function it_uses_the_correct_handler(): void
    {
        $consumer = Builder::create('broker')->withHandler(new FakeConsumer);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $handler = $this->getPropertyWithReflection('handler', $consumer);

        $this->assertInstanceOf(Closure::class, $handler);
    }

    #[Test]
    public function it_can_set_max_messages(): void
    {
        $consumer = Builder::create('broker')->withMaxMessages(2);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $maxMessages = $this->getPropertyWithReflection('maxMessages', $consumer);

        $this->assertEquals(2, $maxMessages);
    }

    #[Test]
    public function it_can_set_max_commit_retries(): void
    {
        $consumer = Builder::create('broker')->withMaxCommitRetries(2);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $maxCommitRetries = $this->getPropertyWithReflection('maxCommitRetries', $consumer);

        $this->assertEquals(2, $maxCommitRetries);
    }

    #[Test]
    public function it_can_set_the_dead_letter_queue(): void
    {
        $consumer = Builder::create('broker')->subscribe('test')->withDlq('test-topic-dlq');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $dlq = $this->getPropertyWithReflection('dlq', $consumer);

        $this->assertEquals('test-topic-dlq', $dlq);
    }

    #[Test]
    public function it_uses_dlq_suffix_if_dlq_is_null(): void
    {
        $consumer = Builder::create('broker', ['foo'])->withDlq();

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $dlq = $this->getPropertyWithReflection('dlq', $consumer);

        $this->assertEquals('foo-dlq', $dlq);
    }

    #[Test]
    public function it_can_set_sasl(): void
    {
        $consumer = Builder::create('broker')
            ->withSasl('username', 'password', 'mechanisms');

        $expectedSaslConfig = new Sasl('username', 'password', 'mechanisms');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $saslConfig = $this->getPropertyWithReflection('saslConfig', $consumer);

        $this->assertEquals($expectedSaslConfig, $saslConfig);
    }

    #[Test]
    public function it_can_add_middlewares_to_the_handler(): void
    {
        $consumer = Builder::create('broker', ['foo'], 'group')
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
        $consumer = Builder::create('broker', ['foo'], 'group')
            ->withMiddleware(new TestMiddleware);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $middlewares = $this->getPropertyWithReflection('middlewares', $consumer);

        $this->assertIsArray($middlewares);

        $this->assertIsCallable($middlewares[0]);
    }

    #[Test]
    public function it_can_set_security_protocol(): void
    {
        $consumer = Builder::create('broker', ['foo'], 'group')
            ->withSecurityProtocol('security');

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $securityProtocol = $this->getPropertyWithReflection('securityProtocol', $consumer);

        $this->assertEquals('security', $securityProtocol);
    }

    #[Test]
    public function it_can_set_security_protocol_via_sasl_config(): void
    {
        $consumer = Builder::create('broker', ['foo'], 'group')
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
        $consumer = Builder::create('broker')->withAutoCommit();

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $autoCommit = $this->getPropertyWithReflection('autoCommit', $consumer);

        $this->assertTrue($autoCommit);

        $consumer = Builder::create('broker')->withAutoCommit(false);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $autoCommit = $this->getPropertyWithReflection('autoCommit', $consumer);

        $this->assertFalse($autoCommit);
    }

    #[Test]
    public function it_can_set_stop_after_last_message(): void
    {
        $consumer = Builder::create('broker')->stopAfterLastMessage();

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $autoCommit = $this->getPropertyWithReflection('stopAfterLastMessage', $consumer);

        $this->assertTrue($autoCommit);

        $consumer = Builder::create('broker')->stopAfterLastMessage(false);

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $autoCommit = $this->getPropertyWithReflection('stopAfterLastMessage', $consumer);

        $this->assertFalse($autoCommit);
    }

    #[Test]
    public function it_can_set_consumer_options(): void
    {
        $consumer = Builder::create('broker')
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
        $consumer = Builder::create('broker')
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
        $consumer = Builder::create('broker')
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

        Builder::create('broker')
            ->withRebalanceStrategy('invalid');
    }

    #[Test]
    public function it_can_specify_brokers_using_with_brokers(): void
    {
        $consumer = Builder::create('broker')->withBrokers('my-test-broker');

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
        $consumer = Builder::create('broker')
            ->usingCommitterFactory($adhocCommitterFactory)
            ->build();

        $committerFactory = $this->getPropertyWithReflection('committerFactory', $consumer);
        $this->assertInstanceOf($adhocCommitterFactory::class, $committerFactory);
    }

    #[Test]
    public function it_cant_create_a_consumer_with_dlq_without_subscribing_to_any_topics(): void
    {
        $this->expectException(ConsumerException::class);

        Builder::create('broker')->withDlq();
    }

    #[Test]
    public function it_can_set_partition_assignment_callback(): void
    {
        $called = false;
        $receivedPartitions = null;

        $consumer = Builder::create('broker', ['test-topic'], 'group')
            ->withPartitionAssignmentCallback(function ($partitions) use (&$called, &$receivedPartitions) {
                $called = true;
                $receivedPartitions = $partitions;
            });

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        $partitionAssignmentCallback = $this->getPropertyWithReflection('partitionAssignmentCallback', $consumer);
        $this->assertInstanceOf(Closure::class, $partitionAssignmentCallback);

        // Verify that a rebalance callback was set
        $callbacks = $this->getPropertyWithReflection('callbacks', $consumer);
        $this->assertArrayHasKey('setRebalanceCb', $callbacks);
        $this->assertIsCallable($callbacks['setRebalanceCb']);
    }

    #[Test]
    public function it_can_set_assign_partitions_with_offsets_callback(): void
    {
        $called = false;
        $receivedPartitions = null;

        $consumer = Builder::create('broker', ['test-topic'], 'group')
            ->assignPartitionsWithOffsets(function ($partitions) use (&$called, &$receivedPartitions) {
                $called = true;
                $receivedPartitions = $partitions;

                // Return the same partitions for testing
                return $partitions;
            });

        $this->assertInstanceOf(Consumer::class, $consumer->build());

        // Verify that a rebalance callback was set
        $callbacks = $this->getPropertyWithReflection('callbacks', $consumer);
        $this->assertArrayHasKey('setRebalanceCb', $callbacks);
        $this->assertIsCallable($callbacks['setRebalanceCb']);
    }
}

final class TestMiddleware
{
    public function __invoke(Message $message, callable $next)
    {
        return $next($message);
    }
}
