<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Config;

use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ConfigFileTest extends LaravelKafkaTestCase
{
    #[Test]
    public function the_default_consumer_group_is_named_after_the_application(): void
    {
        putenv('APP_NAME=My Shop');

        try {
            $config = require __DIR__.'/../../config/kafka.php';
        } finally {
            putenv('APP_NAME');
        }

        $this->assertSame('my-shop', $config['connections']['default']['consumer']['group_id']);
    }
}
