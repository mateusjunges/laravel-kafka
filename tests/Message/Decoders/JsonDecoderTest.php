<?php

namespace Junges\Kafka\Tests\Message\Decoders;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Message\Decoders\JsonDecoder;
use Junges\Kafka\Tests\LaravelKafkaTestCase as TestCase;

class JsonDecoderTest extends TestCase
{
    public function testDecode(): void
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn('{"name":"foo"}');
        $decoder = new JsonDecoder();
        $result = $decoder->decode($message);

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
        $decoder = new JsonDecoder();

        $this->expectException(\JsonException::class);

        $decoder->decode($message);
    }
}