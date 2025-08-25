<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Message\Deserializers;

use JsonException;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase as TestCase;
use PHPUnit\Framework\Attributes\Test;

final class JsonDeserializerTest extends TestCase
{
    #[Test]
    public function deserialize(): void
    {
        $message = $this->getMockForAbstractClass(ConsumerMessage::class);
        $message->expects($this->exactly(2))->method('getBody')->willReturn('{"name":"foo"}');
        $deserializer = new JsonDeserializer;
        $result = $deserializer->deserialize($message);

        $this->assertInstanceOf(ConsumerMessage::class, $result);
        $this->assertEquals(['name' => 'foo'], $result->getBody());
    }

    #[Test]
    public function deserialize_non_json(): void
    {
        $message = $this->getMockForAbstractClass(ConsumerMessage::class);
        $message->expects($this->exactly(2))->method('getBody')->willReturn('test');
        $deserializer = new JsonDeserializer;

        $this->expectException(JsonException::class);

        $deserializer->deserialize($message);
    }
}
