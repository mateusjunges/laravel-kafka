<?php declare(strict_types=1);

namespace Junges\Kafka\Config;

/** SASL credentials. The security protocol is part of the connection and consumer configuration. */
class Sasl
{
    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly string $mechanism,
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
}
