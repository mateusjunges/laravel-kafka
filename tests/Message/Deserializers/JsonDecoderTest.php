<?php

namespace Junges\Kafka\Tests\Message\Deserializers;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Message\Deserializers\JsonDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase as TestCase;

class JsonDecoderTest extends TestCase
{
    public function testDecode(): void
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn('{"name":"foo"}');
        $decoder = new JsonDeserializer();
        $result = $decoder->deserialize($message);

        $this->assertInstanceOf(KafkaConsumerMessage::class, $result);
        $this->assertEquals(['name' => 'foo'], $result->getBody());
    }

    /**
     * @return void
     */
    public function testDecodeNonJson(): void
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn('test');
        $decoder = new JsonDeserializer();

        $this->expectException(\JsonException::class);

        $decoder->deserialize($message);
    }
}
