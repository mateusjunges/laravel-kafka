<?php

namespace Junges\Kafka\Config;

class Sasl
{
    private string $username;
    private string $password;
    private string $mechanisms;

    public function __construct(string $username, string $password, string $mechanisms)
    {
        $this->username = $username;
        $this->password = $password;
        $this->mechanisms = $mechanisms;
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
}
