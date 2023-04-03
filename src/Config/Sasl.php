<?php

namespace Junges\Kafka\Config;

class Sasl
{
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $password;
    /**
     * @var string
     */
    private $mechanisms;
    /**
     * @var string
     */
    private $securityProtocol = 'SASL_PLAINTEXT';
    public function __construct(string $username, string $password, string $mechanisms, string $securityProtocol = 'SASL_PLAINTEXT')
    {
        $this->username = $username;
        $this->password = $password;
        $this->mechanisms = $mechanisms;
        $this->securityProtocol = $securityProtocol;
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
