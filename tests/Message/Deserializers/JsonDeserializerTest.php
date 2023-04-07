<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Message\Deserializers;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase as TestCase;

final class JsonDeserializerTest extends TestCase
{
    public function testDeserialize(): void
    {
        $message = $this->getMockForAbstractClass(ConsumerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn('{"name":"foo"}');
        $deserializer = new JsonDeserializer();
        $result = $deserializer->deserialize($message);

        $this->assertInstanceOf(ConsumerMessage::class, $result);
        $this->assertEquals(['name' => 'foo'], $result->getBody());
    }

    public function testDeserializeNonJson(): void
    {
        $message = $this->getMockForAbstractClass(ConsumerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn('test');
        $deserializer = new JsonDeserializer();

        $this->expectException(\JsonException::class);

        $deserializer->deserialize($message);
    }
}
