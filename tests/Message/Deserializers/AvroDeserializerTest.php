<?php

namespace Junges\Kafka\Tests\Message\Deserializers;

use AvroSchema;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use Junges\Kafka\Contracts\AvroSchemaRegistry;
use Junges\Kafka\Contracts\KafkaAvroSchemaRegistry;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Message\Deserializers\AvroDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class AvroDeserializerTest extends LaravelKafkaTestCase
{
    public function testDeserializeTombstone()
    {
        $message = $this->getMockForAbstractClass(KafkaConsumerMessage::class);
        $message->expects($this->once())->method('getBody')->willReturn(null);

        $registry = $this->getMockForAbstractClass(AvroSchemaRegistry::class);
        $registry->expects($this->never())->method('hasBodySchemaForTopic');
        $registry->expects($this->never())->method('hasKeySchemaForTopic');

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->never())->method('decodeMessage');

        $deserializer = new AvroDeserializer($registry, $recordSerializer);

        $result = $deserializer->deserialize($message);

        $this->assertInstanceOf(KafkaConsumerMessage::class, $result);
        $this->assertNull($result->getBody());
    }

    public function testDeserializeWithSchema()
    {
        $schemaDefinition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects($this->exactly(2))->method('getDefinition')->willReturn($schemaDefinition);

        $message = $this->getMockForAbstractClass(KafkaConsumerMessage::class);
        $message->expects($this->exactly(3))->method('getTopicName')->willReturn('test-topic');
        $message->expects($this->once())->method('getPartition')->willReturn(0);
        $message->expects($this->once())->method('getOffset')->willReturn(1);
        $message->expects($this->once())->method('getTimestamp')->willReturn(time());
        $message->expects($this->exactly(2))->method('getKey')->willReturn('test-key');
        $message->expects($this->exactly(2))->method('getBody')->willReturn('body');
        $message->expects($this->once())->method('getHeaders')->willReturn([]);

        $registry = $this->getMockForAbstractClass(AvroSchemaRegistry::class);
        $registry->expects($this->once())->method('getBodySchemaForTopic')->willReturn($avroSchema);
        $registry->expects($this->once())->method('getKeySchemaForTopic')->willReturn($avroSchema);
        $registry->expects($this->once())->method('hasBodySchemaForTopic')->willReturn(true);
        $registry->expects($this->once())->method('hasKeySchemaForTopic')->willReturn(true);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->exactly(2))
            ->method('decodeMessage')
            ->withConsecutive(
                [$message->getBody(), $schemaDefinition],
                [$message->getKey(), $schemaDefinition],
            )
            ->willReturnOnConsecutiveCalls(['test'], 'decoded-key');

        $deserializer = new AvroDeserializer($registry, $recordSerializer);

        $result = $deserializer->deserialize($message);

        $this->assertInstanceOf(KafkaConsumerMessage::class, $result);
        $this->assertSame(['test'], $result->getBody());
        $this->assertSame('decoded-key', $result->getKey());
    }

    public function testDeserializeKeyMode()
    {
        $schemaDefinition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects($this->once())->method('getDefinition')->willReturn($schemaDefinition);

        $message = $this->getMockForAbstractClass(KafkaConsumerMessage::class);
        $message->expects($this->exactly(3))->method('getTopicName')->willReturn('test-topic');
        $message->expects($this->once())->method('getPartition')->willReturn(0);
        $message->expects($this->once())->method('getOffset')->willReturn(1);
        $message->expects($this->once())->method('getTimestamp')->willReturn(time());
        $message->expects($this->exactly(2))->method('getKey')->willReturn('test-key');
        $message->expects($this->once())->method('getBody')->willReturn('body');
        $message->expects($this->once())->method('getHeaders')->willReturn([]);

        $registry = $this->getMockForAbstractClass(AvroSchemaRegistry::class);
        $registry->expects($this->never())->method('getBodySchemaForTopic');
        $registry->expects($this->once())->method('getKeySchemaForTopic')->willReturn($avroSchema);
        $registry->expects($this->once())->method('hasBodySchemaForTopic')->willReturn(false);
        $registry->expects($this->once())->method('hasKeySchemaForTopic')->willReturn(true);


        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->once())->method('decodeMessage')->with($message->getKey(), $schemaDefinition)->willReturn('decoded-key');

        $decoder = new AvroDeserializer($registry, $recordSerializer);

        $result = $decoder->deserialize($message);

        $this->assertInstanceOf(KafkaConsumerMessage::class, $result);
        $this->assertSame('decoded-key', $result->getKey());
        $this->assertSame('body', $result->getBody());
    }

    public function testDeserializeBodyMode()
    {
        $schemaDefinition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects($this->once())->method('getDefinition')->willReturn($schemaDefinition);

        $message = $this->getMockForAbstractClass(KafkaConsumerMessage::class);
        $message->expects($this->exactly(3))->method('getTopicName')->willReturn('test-topic');
        $message->expects($this->once())->method('getPartition')->willReturn(0);
        $message->expects($this->once())->method('getOffset')->willReturn(1);
        $message->expects($this->once())->method('getTimestamp')->willReturn(time());
        $message->expects($this->once())->method('getKey')->willReturn('test-key');
        $message->expects($this->exactly(2))->method('getBody')->willReturn('body');
        $message->expects($this->once())->method('getHeaders')->willReturn([]);

        $registry = $this->getMockForAbstractClass(AvroSchemaRegistry::class);
        $registry->expects($this->once())->method('getBodySchemaForTopic')->willReturn($avroSchema);
        $registry->expects($this->never())->method('getKeySchemaForTopic');
        $registry->expects($this->once())->method('hasBodySchemaForTopic')->willReturn(true);
        $registry->expects($this->once())->method('hasKeySchemaForTopic')->willReturn(false);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->once())->method('decodeMessage')->with($message->getBody(), $schemaDefinition)->willReturn(['test']);

        $decoder = new AvroDeserializer($registry, $recordSerializer);

        $result = $decoder->deserialize($message);

        $this->assertInstanceOf(KafkaConsumerMessage::class, $result);
        $this->assertSame('test-key', $result->getKey());
        $this->assertSame(['test'], $result->getBody());
    }

    public function testGetRegistry()
    {
        $registry = $this->getMockForAbstractClass(AvroSchemaRegistry::class);
        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();

        $decoder = new AvroDeserializer($registry, $recordSerializer);

        $this->assertSame($registry, $decoder->getRegistry());
    }
}
