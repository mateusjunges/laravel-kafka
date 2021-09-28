<?php

namespace Junges\Kafka\Tests\Message\Deserializers;

use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Message\Deserializers\NullDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class NullDeserializerTest extends LaravelKafkaTestCase
{
    public function testDeserialize(): void
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessage::class);

        $this->assertSame($message, (new NullDeserializer())->deserialize($message));
    }
}
