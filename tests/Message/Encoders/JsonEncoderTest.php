<?php

namespace Junges\Kafka\Tests\Message\Encoders;

use JsonException;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Message\Encoders\JsonEncoder;
use Monolog\Test\TestCase;

class JsonEncoderTest extends TestCase
{
    public function testEncode()
    {
        $message = $this->getMockForAbstractClass(KafkaProducerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn(['name' => 'foo']);
        $message->expects($this->once())->method('withBody')->with('{"name":"foo"}')->willReturn($message);

        $encoder = $this->getMockForAbstractClass(JsonEncoder::class);

        $this->assertSame($message, $encoder->encode($message));
    }

    public function testEncodeThrowsException(): void
    {
        $message = $this->getMockForAbstractClass(KafkaProducerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn(chr(255));

        $encoder = $this->getMockForAbstractClass(JsonEncoder::class);

        $this->expectException(JsonException::class);

        $encoder->encode($message);
    }
}
