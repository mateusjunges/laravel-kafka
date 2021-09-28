<?php

namespace Junges\Kafka\Tests\Message\Serializers;

use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Message\Serializers\NullSerializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class NullSerializerTest extends LaravelKafkaTestCase
{
    public function testSerializer(): void
    {
        $message = $this->getMockForAbstractClass(KafkaProducerMessage::class);

        $this->assertSame($message, (new NullSerializer())->serialize($message));
    }
}
