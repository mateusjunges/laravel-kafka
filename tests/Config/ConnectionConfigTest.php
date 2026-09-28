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
        $this->assertSame('SASL_SSL', $config->sasl->getSecurityProtocol());
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
}
