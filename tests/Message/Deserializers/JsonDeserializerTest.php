<?php

namespace Junges\Kafka\Tests\Message\Deserializers;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase as TestCase;

class JsonDeserializerTest extends TestCase
{
    public function testDeserialize(): void
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn('{"name":"foo"}');
        $deserializer = new JsonDeserializer();
        $result = $deserializer->deserialize($message);

        $this->assertInstanceOf(KafkaConsumerMessage::class, $result);
        $this->assertEquals(['name' => 'foo'], $result->getBody());
    }

    /**
     * @return void
     */
    public function testDeserializeNonJson(): void
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn('test');
        $deserializer = new JsonDeserializer();

        $this->expectException(\JsonException::class);

        $deserializer->deserialize($message);
    }
}
