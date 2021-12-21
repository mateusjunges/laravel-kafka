<?php

namespace Junges\Kafka\Tests\Config;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class ConfigTest extends LaravelKafkaTestCase
{
    public function testItReturnsDefaultKafkaConfiguration()
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            handler: $this->createMock(Handler::class),
            sasl: null,
            dlq: null,
        );

        $expectedOptions = [
            'auto.offset.reset' => 'latest',
            'enable.auto.commit' => 'true',
            'compression.codec' => 'snappy',
            'group.id' => 'group',
            'bootstrap.servers' => 'broker',
            'metadata.broker.list' => 'broker',
        ];

        $this->assertEquals(
            $expectedOptions,
            $config->getConsumerOptions()
        );
    }

    public function testItOverrideDefaultOptionsIfUsingCustom()
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'security',
            commit: 1,
            groupId: 'group',
            handler: $this->createMock(Handler::class),
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
            'compression.codec' => 'gzip',
            'group.id' => 'group',
            'bootstrap.servers' => 'broker',
            'metadata.broker.list' => 'broker',
        ];

        $this->assertEquals(
            $expectedOptions,
            $config->getConsumerOptions()
        );
    }

    public function testItUsesSaslConfigWhenSet()
    {
        $config = new Config(
            broker: 'broker',
            topics: ['topic'],
            securityProtocol: 'SASL_SSL',
            commit: 1,
            groupId: 'group',
            handler: $this->createMock(Handler::class),
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
            'compression.codec' => 'gzip',
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

    public function testItReturnsProducerOptions()
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
            handler: $this->createMock(Handler::class),
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

    public function testItAcceptsCustomOptionsForProducersConfig()
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
            handler: $this->createMock(Handler::class),
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
}
