<?php

namespace Junges\Kafka\Tests\Message\Serializers;

use FlixTech\AvroSerializer\Objects\RecordSerializer;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Exceptions\Serializers\AvroSerializerException;
use Junges\Kafka\Message\KafkaAvroSchema;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Message\Registry\AvroSchemaRegistry;
use Junges\Kafka\Message\Serializers\AvroSerializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;

class AvroSerializerTest extends LaravelKafkaTestCase
{
    public function testSerializeTombstone()
    {
        $producerMessage = m::mock(Message::class);
        $producerMessage->expects('getTopicName')->times(2);
        $producerMessage->expects('getKey');
        $producerMessage->expects('getBody')->times(2)->andReturn(null);

        $registry = m::mock(AvroSchemaRegistry::class);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->never())->method('encodeRecord');

        $serializer = new AvroSerializer($registry, $recordSerializer);

        $result = $serializer->serialize($producerMessage);

        $this->assertInstanceOf(KafkaProducerMessage::class, $result);
        $this->assertSame($producerMessage, $result);
        $this->assertNull($result->getBody());
    }

    public function testSerializeWithoutSchemaDefinition()
    {
        $avroSchema = m::mock(KafkaAvroSchema::class);
        $avroSchema->expects('getName')->times(3)->andReturn('test');
        $avroSchema->expects('getDefinition')->once()->andReturn(null);

        $producerMessage = m::mock(Message::class);
        $producerMessage->expects('getTopicName')->once()->andReturn('topic');
        $producerMessage->expects('getBody')->andReturn([]);

        $registry = m::mock(AvroSchemaRegistry::class);
        $registry->expects('hasBodySchemaForTopic')->andReturn(true);
        $registry->expects('getBodySchemaForTopic')->andReturn($avroSchema);

        $this->expectException(AvroSerializerException::class);
        $this->expectExceptionMessage(
            sprintf(
                AvroSerializerException::UNABLE_TO_LOAD_DEFINITION_MESSAGE,
                $avroSchema->getName()
            )
        );

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();

        $serializer = new AvroSerializer($registry, $recordSerializer);
        $serializer->serialize($producerMessage);
    }

    public function testSerializeSuccessWithSchema()
    {
        $schemaDefinition = $this->getMockBuilder(\AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = m::mock(KafkaAvroSchema::class);
        $avroSchema->expects('getName')->times(2)->andReturn('schemaName');
        $avroSchema->expects('getVersion')->never();
        $avroSchema->expects('getDefinition')->times(2)->andReturn($schemaDefinition);

        $registry = m::mock(AvroSchemaRegistry::class);
        $registry->expects('getBodySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('getKeySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('hasBodySchemaForTopic')->andReturn(true);
        $registry->expects('hasKeySchemaForTopic')->andReturn(true);

        $producerMessage = m::mock(Message::class);
        $producerMessage->expects('getTopicName')->times(2)->andReturn('test');
        $producerMessage->expects('getBody')->andReturn([]);
        $producerMessage->expects('getKey')->andReturn('test-key');
        $producerMessage->expects('withBody')->with('encodedValue')->andReturn($producerMessage);
        $producerMessage->expects('withKey')->with('encodedKey')->andReturn($producerMessage);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer
            ->expects($this->exactly(2))
            ->method('encodeRecord')
            ->willReturnOnConsecutiveCalls('encodedValue', 'encodedKey');

        $serializer = new AvroSerializer($registry, $recordSerializer);

        $this->assertSame($producerMessage, $serializer->serialize($producerMessage));
    }

    public function testSerializeKeyMode()
    {
        $schemaDefinition = $this->getMockBuilder(\AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = $this->mock(KafkaAvroSchema::class);
        $avroSchema->expects('getName')->twice()->andReturn('schemaName');
        $avroSchema->expects('getVersion')->never();
        $avroSchema->expects('getDefinition')->twice()->andReturn($schemaDefinition);

        $registry = $this->mock(AvroSchemaRegistry::class);
        $registry->expects('getBodySchemaForTopic')->never();
        $registry->expects('getKeySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('hasBodySchemaForTopic')->andReturn(false);
        $registry->expects('hasKeySchemaForTopic')->andReturn(true);

        $producerMessage = m::mock(KafkaProducerMessage::class);
        $producerMessage->expects('getTopicName')->twice()->andReturn('test');
        $producerMessage->expects('getBody')->andReturn([]);
        $producerMessage->expects('getKey')->andReturn('test-key');
        $producerMessage->expects('withKey')->with('encodedKey')->andReturn($producerMessage);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->once())->method('encodeRecord')->with($avroSchema->getName(), $avroSchema->getDefinition(), 'test-key')->willReturn('encodedKey');

        $serializer = new AvroSerializer($registry, $recordSerializer);

        $this->assertSame($producerMessage, $serializer->serialize($producerMessage));
    }

    public function testSerializeBodyMode()
    {
        $schemaDefinition = $this->getMockBuilder(\AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = m::mock(KafkaAvroSchema::class);
        $avroSchema->expects('getName')->twice()->andReturn('schemaName');
        $avroSchema->expects('getDefinition')->times(2)->andReturn($schemaDefinition);

        $registry = m::mock(AvroSchemaRegistry::class);
        $registry->expects('getBodySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('hasKeySchemaForTopic')->andReturn(false);
        $registry->expects('hasBodySchemaForTopic')->andReturn(true);

        $producerMessage = m::mock(Message::class);
        $producerMessage->expects('getTopicName')->times(2)->andReturn('test');
        $producerMessage->expects('getBody')->once()->andReturn([]);
        $producerMessage->expects('getKey')->once()->andReturn('test-key');
        $producerMessage->expects('withBody')->once()->with('encodedBody')->andReturn($producerMessage);
        $producerMessage->expects('withKey')->never();

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->once())->method('encodeRecord')->with($avroSchema->getName(), $avroSchema->getDefinition(), [])->willReturn('encodedBody');

        $serializer = new AvroSerializer($registry, $recordSerializer);

        $this->assertSame($producerMessage, $serializer->serialize($producerMessage));
    }
}
