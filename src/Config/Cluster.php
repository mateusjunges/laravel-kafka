<?php

namespace Junges\Kafka\Config;

use JetBrains\PhpStorm\Pure;

class Cluster
{
    public function __construct(
        private string  $brokers,
        private string  $compression,
        private int     $partition = 0,
        private bool    $debug = false,
        private ?string $securityProtocol = 'plaintext',
        private ?string $saslSecurityProtocol = null,
        private ?string $saslUsername = null,
        private ?string $saslPassword = null,
        private ?string $saslMechanism = null,
    ) {
    }

    /**
     * Create a new Cluster instance from config array.
     *
     * @param array $config
     * @return $this
     */
    #[Pure]
    public static function createFromConfig(array $config): self
    {
        return new static(
            brokers: $config['brokers'],
            compression: $config['compression'],
            partition: $config['partition'],
            debug: $config['debug'],
            securityProtocol: $config['security_protocol'],
            saslSecurityProtocol: $config['sasl_security_protocol'] ?? null,
            saslUsername: $config['sasl_username'] ?? null,
            saslPassword: $config['sasl_password'] ?? null,
            saslMechanism: $config['sasl_mechanism'] ?? null
        );
    }

    public function getBrokers(): string
    {
        return $this->brokers;
    }

    public function getCompression(): string
    {
        return $this->compression;
    }

    public function getPartition(): int
    {
        return $this->partition;
    }

    public function getSecurityProtocol(): string
    {
        return $this->securityProtocol;
    }

    public function getSaslSecurityProtocol(): ?string
    {
        return $this->saslSecurityProtocol;
    }

    public function getSaslUsername(): ?string
    {
        return $this->saslUsername;
    }

    public function getSaslPassword(): ?string
    {
        return $this->saslPassword;
    }

    public function getSaslMechanism(): ?string
    {
        return $this->saslMechanism;
    }

    #[Pure]
    public function getSasl(): ?Sasl
    {
        if (! $this->isSaslEnabled()) {
            return null;
        }

        return new Sasl(
            username: $this->getSaslUsername(),
            password: $this->getSaslPassword(),
            mechanisms: $this->getSaslMechanism(),
            securityProtocol: $this->getSaslSecurityProtocol()
        );
    }

    public function isSaslEnabled(): bool
    {
        return $this->saslMechanism !== null
            && $this->saslPassword !== null
            && $this->saslUsername !== null
            && $this->saslSecurityProtocol !== null;
    }

    public function isDebugEnabled(): bool
    {
        return $this->debug;
    }
}
