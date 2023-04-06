<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Config;

use Junges\Kafka\BatchRepositories\NullBatchRepository;
use Junges\Kafka\Config\BatchConfig;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\NullBatchConfig;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Consumers\CallableBatchConsumer;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Support\Timer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

final class ConfigTest extends LaravelKafkaTestCase
{
    public function testItReturnsDefaultKafkaConfiguration(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $this->createMock(Consumer::class),
            sasl: null,
            dlq: null,
        );

        $expectedOptions = [
            'auto.offset.reset' => 'latest',
            'enable.auto.commit' => 'true',
            'group.id' => 'group',
            'bootstrap.servers' => 'broker',
            'metadata.broker.list' => 'broker',
        ];

        $this->assertEquals(
            $expectedOptions,
            $config->getConsumerOptions()
        );
    }

    public function testItOverrideDefaultOptionsIfUsingCustom(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            consumer: $this->createMock(Consumer::class),
            sasl: null,
            dlq: null,
            maxMessages: -1,
            maxCommitRetries: 6,
            autoCommit: true,
            customOptions: ['auto.offset.reset' => 'smallest', 'compression.codec' => 'gzip']
        );

        $expectedOptions = [
            'auto.offset.reset' => 'smallest',
            'enable.auto.commit' => 'true',
            'group.id' => 'group',
            'bootstrap.servers' => 'broker',
            'metadata.broker.list' => 'broker',
        ];

        $this->assertEquals(
            $expectedOptions,
            $config->getConsumerOptions()
        );
    }

    public function testItUsesSaslConfigWhenSet(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'SASL_SSL',
            commit: 1,
            groupId: 'group',
            consumer: $this->createMock(Consumer::class),
            sasl: new Sasl('foo', 'bar', 'SCRAM-SHA-512', 'SASL_SSL'),
            dlq: null,
            maxMessages: -1,
            maxCommitRetries: 6,
            autoCommit: true,
            customOptions: ['auto.offset.reset' => 'smallest', 'compression.codec' => 'gzip']
        );

        $expectedOptions = [
            'auto.offset.reset' => 'smallest',
            'enable.auto.commit' => 'true',
            'group.id' => 'group',
            'bootstrap.servers' => 'broker',
            'metadata.broker.list' => 'broker',
            'security.protocol' => 'SASL_SSL',
            'sasl.username' => 'foo',
            'sasl.password' => 'bar',
            'sasl.mechanisms' => 'SCRAM-SHA-512',
        ];

        $this->assertEquals(
            $expectedOptions,
            $config->getConsumerOptions()
        );
    }

    public function testItReturnsProducerOptions(): void
    {
        $sasl = new Sasl(
            username: 'user',
            password: 'pass',
            mechanisms: 'mec'
        );

        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'SASL_PLAINTEXT',
            commit: 1,
            groupId: 'group',
            consumer: $this->createMock(Consumer::class),
            sasl: $sasl,
            dlq: null,
        );

        $expectedOptions = [
            'compression.codec' => 'snappy',
            'bootstrap.servers' => 'broker',
            'sasl.username' => 'user',
            'sasl.password' => 'pass',
            'sasl.mechanisms' => 'mec',
            'metadata.broker.list' => 'broker',
            'security.protocol' => 'SASL_PLAINTEXT',
        ];

        $this->assertEquals(
            $expectedOptions,
            $config->getProducerOptions()
        );
    }

    public function testItAcceptsCustomOptionsForProducersConfig(): void
    {
        $customOptions = [
            'bootstrap.servers' => '[REMOTE_ADDRESS]',
            'metadata.broker.list' => '[REMOTE_ADDRESS]',
            'security.protocol' => 'SASL_SSL',
            'sasl.mechanisms' => 'PLAIN',
            'sasl.username' => '[API_KEY]',
            'sasl.password' => '[API_KEY]',
        ];

        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'SASL_PLAINTEXT',
            commit: 1,
            groupId: 'group',
            consumer: $this->createMock(Consumer::class),
            dlq: null,
            customOptions: $customOptions
        );

        $expectedOptions = [
            'compression.codec' => 'snappy',
            'bootstrap.servers' => '[REMOTE_ADDRESS]',
            'metadata.broker.list' => '[REMOTE_ADDRESS]',
            'security.protocol' => 'SASL_SSL',
            'sasl.mechanisms' => 'PLAIN',
            'sasl.username' => '[API_KEY]',
            'sasl.password' => '[API_KEY]',
        ];

        $this->assertEquals(
            $expectedOptions,
            $config->getProducerOptions()
        );
    }

    public function testSaslCanBeUsedWithLowercaseConfigKeys(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'sasl_plaintext',
            commit: 1,
            groupId: 'group',
            consumer: $this->createMock(Consumer::class),
            sasl: new Sasl(
                username: 'username',
                password: 'password',
                mechanisms: 'mechanisms',
                securityProtocol: 'ssl_plaintext',
            ),
            dlq: null
        );

        $expectedOptions = [
            'compression.codec' => 'snappy',
            'bootstrap.servers' => 'broker',
            'metadata.broker.list' => 'broker',
            'security.protocol' => 'ssl_plaintext',
            'sasl.mechanisms' => 'mechanisms',
            'sasl.username' => 'username',
            'sasl.password' => 'password',
        ];

        $this->assertEquals(
            $expectedOptions,
            $config->getProducerOptions()
        );
    }

    public function testItCreatesNullBatchConfigIfNullIsPassed(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'SASL_PLAINTEXT',
            commit: 1,
            groupId: 'group',
            consumer: $this->createMock(Consumer::class),
            dlq: null,
        );

        $this->assertInstanceOf(NullBatchConfig::class, $config->getBatchConfig());
    }

    public function testItReturnsGivenBatchConfigIfInstancePassed(): void
    {
        $batchConfig = new BatchConfig(
            new CallableBatchConsumer(function () {
            }),
            new Timer(),
            new NullBatchRepository(),
        );

        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'SASL_PLAINTEXT',
            commit: 1,
            groupId: 'group',
            consumer: $this->createMock(Consumer::class),
            dlq: null,
            batchConfig: $batchConfig,
        );

        $this->assertEquals($batchConfig, $config->getBatchConfig());
        $this->assertInstanceOf(CallableBatchConsumer::class, $batchConfig->getConsumer());
        $this->assertInstanceOf(Timer::class, $batchConfig->getTimer());
        $this->assertInstanceOf(NullBatchRepository::class, $batchConfig->getBatchRepository());
        $this->assertEquals(0, $batchConfig->getBatchReleaseInterval());
        $this->assertEquals(0, $batchConfig->getBatchSizeLimit());
    }
}
