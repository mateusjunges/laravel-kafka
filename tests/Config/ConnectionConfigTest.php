<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Config;

use Junges\Kafka\Config\ConnectionConfig;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ConnectionConfigTest extends LaravelKafkaTestCase
{
    #[Test]
    public function it_reads_the_sasl_mechanism(): void
    {
        $config = ConnectionConfig::fromArray('default', [
            'brokers' => 'broker',
            'security_protocol' => 'SASL_SSL',
            'sasl' => ['username' => 'user', 'password' => 'secret', 'mechanism' => 'SCRAM-SHA-256'],
        ]);

        $this->assertSame('SCRAM-SHA-256', $config->sasl->getMechanism());
        $this->assertSame('SASL_SSL', $config->securityProtocol);
    }

    #[Test]
    public function it_still_reads_the_sasl_mechanisms_key(): void
    {
        $config = ConnectionConfig::fromArray('default', [
            'brokers' => 'broker',
            'sasl' => ['username' => 'user', 'password' => 'secret', 'mechanisms' => 'SCRAM-SHA-512'],
        ]);

        $this->assertSame('SCRAM-SHA-512', $config->sasl->getMechanism());
    }

    #[Test]
    public function it_uses_the_sasl_protocol_matching_the_encryption_when_sasl_credentials_are_set(): void
    {
        $protocols = [
            'PLAINTEXT' => 'SASL_PLAINTEXT',
            'SSL' => 'SASL_SSL',
            'SASL_PLAINTEXT' => 'SASL_PLAINTEXT',
            'SASL_SSL' => 'SASL_SSL',
            null => 'SASL_PLAINTEXT',
        ];

        foreach ($protocols as $configured => $expected) {
            $config = ConnectionConfig::fromArray('default', [
                'brokers' => 'broker',
                'security_protocol' => $configured === '' ? null : $configured,
                'sasl' => ['username' => 'user', 'password' => 'secret'],
            ]);

            $this->assertSame($expected, $config->securityProtocol, 'Configured: '.($configured ?: 'none'));
        }
    }

    #[Test]
    public function it_keeps_the_configured_protocol_without_sasl_credentials(): void
    {
        $config = ConnectionConfig::fromArray('default', ['brokers' => 'broker', 'security_protocol' => 'SSL']);

        $this->assertSame('SSL', $config->securityProtocol);
        $this->assertNull($config->sasl);
    }
}
