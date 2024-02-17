<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Message\Serializers;

use FlixTech\AvroSerializer\Objects\RecordSerializer;
use Junges\Kafka\Contracts\AvroSchemaRegistry;
use Junges\Kafka\Contracts\KafkaAvroSchemaRegistry;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Exceptions\Serializers\AvroSerializerException;
use Junges\Kafka\Message\Serializers\AvroSerializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;

final class AvroSerializerTest extends LaravelKafkaTestCase
{
    public function testSerializeTombstone(): void
    {
        $producerMessage = m::mock(ProducerMessage::class);
        $producerMessage->expects('getBody')->times(2)->andReturn(null);
        $producerMessage->expects('getTopicName')->times(2)->andReturn('test-topic');
        $producerMessage->expects('getKey')->once()->andReturn('test-key');

        $registry = m::mock(AvroSchemaRegistry::class);
        $registry->expects('hasKeySchemaForTopic');

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->never())->method('encodeRecord');

        $serializer = new AvroSerializer($registry, $recordSerializer);

        $result = $serializer->serialize($producerMessage);

        $this->assertInstanceOf(ProducerMessage::class, $result);
        $this->assertSame($producerMessage, $result);
        $this->assertNull($result->getBody());
    }

    public function testSerializeWithoutSchemaDefinition(): void
    {
        $avroSchema = m::mock(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects('getDefinition')->andReturn(null);
        $avroSchema->expects('getName')->times(3)->andReturn('name');

        $producerMessage = m::mock(ProducerMessage::class);
        $producerMessage->expects('getTopicName')->andReturn('test');
        $producerMessage->expects('getBody')->andReturn('test');

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

    public function testSerializeSuccessWithSchema(): void
    {
        $schemaDefinition = $this->getMockBuilder(\AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = m::mock(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects('getName')->twice()->andReturn('schemaName');
        $avroSchema->expects('getDefinition')->twice()->andReturn($schemaDefinition);

        $registry = m::mock(AvroSchemaRegistry::class);
        $registry->expects('getBodySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('getKeySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('hasBodySchemaForTopic')->andReturn(true);
        $registry->expects('hasKeySchemaForTopic')->andReturn(true);

        $producerMessage = m::mock(ProducerMessage::class);
        $producerMessage->expects('getTopicName')->twice()->andReturn('test');
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

    public function testSerializeKeyMode(): void
    {
        $schemaDefinition = $this->getMockBuilder(\AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = m::mock(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects('getName')->twice()->andReturn('schemaName');
        $avroSchema->expects('getDefinition')->twice()->andReturn($schemaDefinition);

        $registry = m::mock(AvroSchemaRegistry::class);
        $registry->expects('getKeySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('hasBodySchemaForTopic')->andReturn(false);
        $registry->expects('hasKeySchemaForTopic')->andReturn(true);

        $producerMessage = m::mock(ProducerMessage::class);
        $producerMessage->expects('getTopicName')->twice()->andReturn('test');
        $producerMessage->expects('getBody')->andReturn([]);
        $producerMessage->expects('getKey')->andReturn('test-key');
        $producerMessage->expects('withKey')->with('encodedKey')->andReturn($producerMessage);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->once())->method('encodeRecord')->with($avroSchema->getName(), $avroSchema->getDefinition(), 'test-key')->willReturn('encodedKey');

        $serializer = new AvroSerializer($registry, $recordSerializer);

        $this->assertSame($producerMessage, $serializer->serialize($producerMessage));
    }

    public function testSerializeBodyMode(): void
    {
        $schemaDefinition = $this->getMockBuilder(\AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = m::mock(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects('getName')->twice()->andReturn('schemaName');
        $avroSchema->expects('getDefinition')->twice()->andReturn($schemaDefinition);

        $registry = m::mock(AvroSchemaRegistry::class);
        $registry->expects('getBodySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('hasBodySchemaForTopic')->andReturn(true);
        $registry->expects('hasKeySchemaForTopic')->andReturn(false);

        $producerMessage = m::mock(ProducerMessage::class);
        $producerMessage->expects('getTopicName')->twice()->andReturn('test');
        $producerMessage->expects('getBody')->andReturn([]);
        $producerMessage->expects('getKey')->andReturn('test-key');
        $producerMessage->expects('withBody')->with('encodedBody')->andReturn($producerMessage);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->once())->method('encodeRecord')->with($avroSchema->getName(), $avroSchema->getDefinition(), [])->willReturn('encodedBody');

        $serializer = new AvroSerializer($registry, $recordSerializer);

        $this->assertSame($producerMessage, $serializer->serialize($producerMessage));
    }

    public function testGetRegistry(): void
    {
        $registry = m::mock(AvroSchemaRegistry::class);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $serializer = new AvroSerializer($registry, $recordSerializer);

        $this->assertSame($registry, $serializer->getRegistry());
    }
}
