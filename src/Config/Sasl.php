<?php

namespace Junges\Kafka\Config;

class Sasl
{
    public function __construct(
        private string $username,
        private string $password,
        private string $mechanisms,
        private string $securityProtocol = 'SASL_PLAINTEXT'
    ) {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getMechanisms(): string
    {
        return $this->mechanisms;
    }

    public function getSecurityProtocol(): string
    {
        return $this->securityProtocol;
    }
}
