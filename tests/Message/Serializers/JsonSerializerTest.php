<?php

namespace Junges\Kafka\Tests\Message\Serializers;

use JsonException;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Message\Serializers\JsonSerializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase as TestCase;

final class JsonSerializerTest extends TestCase
{
    public function testSerialize(): void
    {
        $message = $this->getMockForAbstractClass(KafkaProducerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn(['name' => 'foo']);
        $message->expects($this->once())->method('withBody')->with('{"name":"foo"}')->willReturn($message);

        $serializer = $this->getMockForAbstractClass(JsonSerializer::class);

        $this->assertSame($message, $serializer->serialize($message));
    }

    public function testSerializeThrowsException(): void
    {
        $message = $this->getMockForAbstractClass(KafkaProducerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn(chr(255));

        $serializer = $this->getMockForAbstractClass(JsonSerializer::class);

        $this->expectException(JsonException::class);

        $serializer->serialize($message);
    }
}
