<?php declare(strict_types=1);

namespace Junges\Kafka\Config;

class Sasl
{
    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly string $mechanism,
        private readonly string $securityProtocol = 'SASL_PLAINTEXT'
    ) {}

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getMechanism(): string
    {
        return $this->mechanism;
    }

    public function getSecurityProtocol(): string
    {
        return $this->securityProtocol;
    }
}
