<?php

namespace Junges\Kafka\Tests\Config;

use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Tests\TestCase;

class SaslTest extends TestCase
{
    public function testGetUsername()
    {
        $sasl = new Sasl(
            username: 'username',
            password: 'password',
            mechanisms: 'mechanisms'
        );

        $this->assertEquals('username', $sasl->getUsername());
    }

    public function testGetPassword()
    {
        $sasl = new Sasl(
            username: 'username',
            password: 'password',
            mechanisms: 'mechanisms'
        );

        $this->assertEquals('password', $sasl->getPassword());
    }

    public function testGetMechanisms()
    {
        $sasl = new Sasl(
            username: 'username',
            password: 'password',
            mechanisms: 'mechanisms'
        );

        $this->assertEquals('mechanisms', $sasl->getMechanisms());
    }
}
