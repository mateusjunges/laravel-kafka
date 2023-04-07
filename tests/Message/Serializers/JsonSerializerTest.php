<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Message\Serializers;

use JsonException;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Message\Serializers\JsonSerializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase as TestCase;

final class JsonSerializerTest extends TestCase
{
    public function testSerialize(): void
    {
        $message = $this->getMockForAbstractClass(ProducerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn(['name' => 'foo']);
        $message->expects($this->once())->method('withBody')->with('{"name":"foo"}')->willReturn($message);

        $serializer = $this->getMockForAbstractClass(JsonSerializer::class);

        $this->assertSame($message, $serializer->serialize($message));
    }

    public function testSerializeThrowsException(): void
    {
        $message = $this->getMockForAbstractClass(ProducerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn(chr(255));

        $serializer = $this->getMockForAbstractClass(JsonSerializer::class);

        $this->expectException(JsonException::class);

        $serializer->serialize($message);
    }
}
