<?php

namespace Junges\Kafka\Tests\Message\Serializers;

use FlixTech\AvroSerializer\Objects\RecordSerializer;
use Junges\Kafka\Contracts\AvroSchemaRegistry;
use Junges\Kafka\Contracts\KafkaAvroSchemaRegistry;
use Junges\Kafka\Contracts\KafkaProducerMessage;
use Junges\Kafka\Exceptions\Encoders\AvroEncoderException;
use Junges\Kafka\Message\Serializers\AvroSerializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class AvroSerializerTest extends LaravelKafkaTestCase
{
    public function testSerializeTombstone()
    {
        $producerMessage = $this->getMockForAbstractClass(KafkaProducerMessage::class);
        $producerMessage->expects($this->exactly(2))->method('getBody')->willReturn(null);

        $registry = $this->getMockForAbstractClass(AvroSchemaRegistry::class);
        $registry->expects($this->never())->method('hasBodySchemaForTopic');
        $registry->expects($this->never())->method('hasKeySchemaForTopic');

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
        $avroSchema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects($this->once())->method('getDefinition')->willReturn(null);

        $producerMessage = $this->getMockForAbstractClass(KafkaProducerMessage::class);
        $producerMessage->expects($this->once())->method('getTopicName')->willReturn('test');
        $producerMessage->expects($this->once())->method('getBody')->willReturn('test');

        $registry = $this->getMockForAbstractClass(AvroSchemaRegistry::class);
        $registry->expects($this->once())->method('hasBodySchemaForTopic')->willReturn(true);
        $registry->expects($this->once())->method('getBodySchemaForTopic')->willReturn($avroSchema);

        $this->expectException(AvroEncoderException::class);
        $this->expectExceptionMessage(
            sprintf(
                AvroEncoderException::UNABLE_TO_LOAD_DEFINITION_MESSAGE,
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

        $avroSchema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects($this->exactly(4))->method('getName')->willReturn('schemaName');
        $avroSchema->expects($this->never())->method('getVersion');
        $avroSchema->expects($this->exactly(4))->method('getDefinition')->willReturn($schemaDefinition);

        $registry = $this->getMockForAbstractClass(AvroSchemaRegistry::class);
        $registry->expects($this->once())->method('getBodySchemaForTopic')->willReturn($avroSchema);
        $registry->expects($this->once())->method('getKeySchemaForTopic')->willReturn($avroSchema);
        $registry->expects($this->once())->method('hasBodySchemaForTopic')->willReturn(true);
        $registry->expects($this->once())->method('hasKeySchemaForTopic')->willReturn(true);

        $producerMessage = $this->getMockForAbstractClass(KafkaProducerMessage::class);
        $producerMessage->expects($this->exactly(2))->method('getTopicName')->willReturn('test');
        $producerMessage->expects($this->once())->method('getBody')->willReturn([]);
        $producerMessage->expects($this->once())->method('getKey')->willReturn('test-key');
        $producerMessage->expects($this->once())->method('withBody')->with('encodedValue')->willReturn($producerMessage);
        $producerMessage->expects($this->once())->method('withKey')->with('encodedKey')->willReturn($producerMessage);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer
            ->expects($this->exactly(2))
            ->method('encodeRecord')
            ->withConsecutive(
                [$avroSchema->getName(), $avroSchema->getDefinition(), []],
                [$avroSchema->getName(), $avroSchema->getDefinition(), 'test-key']
            )->willReturnOnConsecutiveCalls('encodedValue', 'encodedKey');

        $serializer = new AvroSerializer($registry, $recordSerializer);

        $this->assertSame($producerMessage, $serializer->serialize($producerMessage));
    }

    public function testSerializeKeyMode()
    {
        $schemaDefinition = $this->getMockBuilder(\AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects($this->exactly(2))->method('getName')->willReturn('schemaName');
        $avroSchema->expects($this->never())->method('getVersion');
        $avroSchema->expects($this->exactly(2))->method('getDefinition')->willReturn($schemaDefinition);

        $registry = $this->getMockForAbstractClass(AvroSchemaRegistry::class);
        $registry->expects($this->never())->method('getBodySchemaForTopic');
        $registry->expects($this->once())->method('getKeySchemaForTopic')->willReturn($avroSchema);
        $registry->expects($this->once())->method('hasBodySchemaForTopic')->willReturn(false);
        $registry->expects($this->once())->method('hasKeySchemaForTopic')->willReturn(true);

        $producerMessage = $this->getMockForAbstractClass(KafkaProducerMessage::class);
        $producerMessage->expects($this->exactly(2))->method('getTopicName')->willReturn('test');
        $producerMessage->expects($this->once())->method('getBody')->willReturn([]);
        $producerMessage->expects($this->once())->method('getKey')->willReturn('test-key');
        $producerMessage->expects($this->never())->method('withBody');
        $producerMessage->expects($this->once())->method('withKey')->with('encodedKey')->willReturn($producerMessage);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->once())->method('encodeRecord')->with($avroSchema->getName(), $avroSchema->getDefinition(), 'test-key')->willReturn('encodedKey');

        $serializer = new AvroSerializer($registry, $recordSerializer);

        $this->assertSame($producerMessage, $serializer->serialize($producerMessage));
    }

    public function testSerializeBodyMode()
    {
        $schemaDefinition = $this->getMockBuilder(\AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects($this->exactly(2))->method('getName')->willReturn('schemaName');
        $avroSchema->expects($this->never())->method('getVersion');
        $avroSchema->expects($this->exactly(2))->method('getDefinition')->willReturn($schemaDefinition);

        $registry = $this->getMockForAbstractClass(AvroSchemaRegistry::class);
        $registry->expects($this->once())->method('getBodySchemaForTopic')->willReturn($avroSchema);
        $registry->expects($this->never())->method('getKeySchemaForTopic');
        $registry->expects($this->once())->method('hasBodySchemaForTopic')->willReturn(true);
        $registry->expects($this->once())->method('hasKeySchemaForTopic')->willReturn(false);

        $producerMessage = $this->getMockForAbstractClass(KafkaProducerMessage::class);
        $producerMessage->expects($this->exactly(2))->method('getTopicName')->willReturn('test');
        $producerMessage->expects($this->once())->method('getBody')->willReturn([]);
        $producerMessage->expects($this->once())->method('getKey')->willReturn('test-key');
        $producerMessage->expects($this->once())->method('withBody')->with('encodedBody')->willReturn($producerMessage);
        $producerMessage->expects($this->never())->method('withKey');

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->once())->method('encodeRecord')->with($avroSchema->getName(), $avroSchema->getDefinition(), [])->willReturn('encodedBody');

        $serializer = new AvroSerializer($registry, $recordSerializer);

        $this->assertSame($producerMessage, $serializer->serialize($producerMessage));
    }

    public function testGetRegistry()
    {
        $registry = $this->getMockForAbstractClass(AvroSchemaRegistry::class);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $serializer = new AvroSerializer($registry, $recordSerializer);

        $this->assertSame($registry, $serializer->getRegistry());
    }
}
