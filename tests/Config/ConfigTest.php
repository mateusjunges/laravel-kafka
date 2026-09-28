<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Config;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ConfigTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_returns_default_kafka_configuration(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'PLAINTEXT',
            commit: 1,
            groupId: 'group',
            consumer: $this->createMock(Consumer::class),
            sasl: null,
            dlq: null,
        );

        $expectedOptions = [
            'enable.auto.commit' => 'true',
            'group.id' => 'group',
            'bootstrap.servers' => 'broker',
            'metadata.broker.list' => 'broker',
            'security.protocol' => 'PLAINTEXT',
        ];

        $this->assertEquals(
            $expectedOptions,
            $config->getConsumerOptions()
        );
    }

    #[Test]
    public function it_disables_automatic_offset_store_when_stopping_on_failure_with_auto_commit(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'PLAINTEXT',
            commit: 1,
            groupId: 'group',
            consumer: $this->createStub(Consumer::class),
            sasl: null,
            dlq: null,
            autoCommit: true,
            customOptions: ['enable.auto.offset.store' => 'true'],
            stopOnFailure: true,
        );

        $this->assertTrue($config->shouldStoreOffsetsAfterProcessing());
        $this->assertSame('false', $config->getConsumerOptions()['enable.auto.offset.store']);
    }

    #[Test]
    public function it_disables_automatic_offset_store_when_retrying_failed_messages_with_auto_commit(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'PLAINTEXT',
            commit: 1,
            groupId: 'group',
            consumer: $this->createStub(Consumer::class),
            sasl: null,
            dlq: null,
            autoCommit: true,
            failedMessageRetries: 3,
        );

        $this->assertTrue($config->shouldStoreOffsetsAfterProcessing());
        $this->assertSame('false', $config->getConsumerOptions()['enable.auto.offset.store']);
    }

    #[Test]
    public function it_keeps_automatic_offset_store_when_stopping_on_failure_with_manual_commit(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'PLAINTEXT',
            commit: 1,
            groupId: 'group',
            consumer: $this->createStub(Consumer::class),
            sasl: null,
            dlq: null,
            autoCommit: false,
            stopOnFailure: true,
        );

        $this->assertFalse($config->shouldStoreOffsetsAfterProcessing());
        $this->assertArrayNotHasKey('enable.auto.offset.store', $config->getConsumerOptions());
    }

    #[Test]
    public function it_override_default_options_if_using_custom(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'PLAINTEXT',
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
            'security.protocol' => 'PLAINTEXT',
        ];

        $this->assertEquals(
            $expectedOptions,
            $config->getConsumerOptions()
        );
    }

    #[Test]
    public function it_uses_sasl_config_when_set(): void
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

    #[Test]
    public function it_returns_producer_options(): void
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

    #[Test]
    public function it_accepts_custom_options_for_producers_config(): void
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

    #[Test]
    public function sasl_can_be_used_with_lowercase_config_keys(): void
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

    #[Test]
    public function it_sets_the_security_protocol_when_not_using_sasl(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'SSL',
            groupId: 'group',
        );

        $this->assertSame('SSL', $config->getProducerOptions()['security.protocol']);
        $this->assertSame('SSL', $config->getConsumerOptions()['security.protocol']);
    }

    #[Test]
    public function it_converts_boolean_options_to_strings(): void
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            customOptions: ['enable.idempotence' => true, 'enable.partition.eof' => false],
        );

        $this->assertSame('true', $config->getProducerOptions()['enable.idempotence']);
        $this->assertSame('false', $config->getConsumerOptions()['enable.partition.eof']);
    }
}
