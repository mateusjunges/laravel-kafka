<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Config;

use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;

final class SaslTest extends LaravelKafkaTestCase
{
    #[Test]
    public function get_username(): void
    {
        $sasl = new Sasl(
            username: 'username',
            password: 'password',
            mechanisms: 'mechanisms'
        );

        $this->assertEquals('username', $sasl->getUsername());
    }

    #[Test]
    public function get_password(): void
    {
        $sasl = new Sasl(
            username: 'username',
            password: 'password',
            mechanisms: 'mechanisms'
        );

        $this->assertEquals('password', $sasl->getPassword());
    }

    #[Test]
    public function get_mechanisms(): void
    {
        $sasl = new Sasl(
            username: 'username',
            password: 'password',
            mechanisms: 'mechanisms'
        );

        $this->assertEquals('mechanisms', $sasl->getMechanisms());
    }
}
