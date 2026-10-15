<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Console;

use Illuminate\Support\Facades\File;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

final class MakeConsumerCommandTest extends LaravelKafkaTestCase
{
    #[Override]
    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Kafka'));

        parent::tearDown();
    }

    #[Test]
    public function it_creates_a_consumer_class(): void
    {
        $this->artisan('make:kafka-consumer', ['name' => 'OrdersConsumer'])->assertSuccessful();

        $path = app_path('Kafka/Consumers/OrdersConsumer.php');

        $this->assertFileExists($path);
        $this->assertStringContainsString('namespace App\\Kafka\\Consumers;', File::get($path));
        $this->assertStringContainsString('class OrdersConsumer extends KafkaConsumer', File::get($path));
    }
}
