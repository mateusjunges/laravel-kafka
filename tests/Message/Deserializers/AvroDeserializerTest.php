<?php

namespace Junges\Kafka\Tests\Message\Deserializers;

use AvroSchema;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use Junges\Kafka\Contracts\AvroSchemaRegistry;
use Junges\Kafka\Contracts\KafkaAvroSchemaRegistry;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Message\ConsumedMessage;
use Junges\Kafka\Message\Deserializers\AvroDeserializer;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use Mockery as m;

class AvroDeserializerTest extends LaravelKafkaTestCase
{
    public function testDeserializeTombstone()
    {
        $message = m::mock(ConsumedMessage::class);
        $message->expects('getBody')->once()->andReturn(null);
        $message->expects('getTopicName')->times(3)->andReturn('topicName');
        $message->expects('getPartition')->once()->andReturn(0);
        $message->expects('getHeaders')->once()->andReturn([]);
        $message->expects('getKey')->once()->andReturn(1);
        $message->expects('getOffset');
        $message->expects('getTimestamp');

        $registry = m::mock(AvroSchemaRegistry::class);
        $registry->expects('hasKeySchemaForTopic');

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

        $avroSchema = m::mock(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects('getDefinition')->times(2)->andReturn($schemaDefinition);

        $message = m::mock(KafkaConsumerMessage::class);
        $message->expects('getTopicName')->times(3)->andReturn('test-topic');
        $message->expects('getPartition')->andReturn(0);
        $message->expects('getOffset')->andReturn(1);
        $message->expects('getTimestamp')->andReturn(time());
        $message->expects('getKey')->andReturn('test-key');
        $message->expects('getBody')->andReturn('body');
        $message->expects('getHeaders')->andReturn([]);

        $registry = m::mock(AvroSchemaRegistry::class);
        $registry->expects('getBodySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('getKeySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('hasBodySchemaForTopic')->andReturn(true);
        $registry->expects('hasKeySchemaForTopic')->andReturn(true);

        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->exactly(2))
            ->method('decodeMessage')
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

        $avroSchema = m::mock(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects('getDefinition')->andReturn($schemaDefinition);

        $message = m::mock(KafkaConsumerMessage::class);
        $message->expects('getTopicName')->times(3)->andReturn('test-topic');
        $message->expects('getPartition')->andReturn(0);
        $message->expects('getOffset')->andReturn(1);
        $message->expects('getTimestamp')->andReturn(time());
        $message->expects('getKey')->times(2)->andReturn('test-key');
        $message->expects('getBody')->andReturn('body');
        $message->expects('getHeaders')->andReturn([]);

        $registry = m::mock(AvroSchemaRegistry::class);
        $registry->expects('getKeySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('hasBodySchemaForTopic')->andReturn(false);
        $registry->expects('hasKeySchemaForTopic')->andReturn(true);


        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();
        $recordSerializer->expects($this->once())
            ->method('decodeMessage')
            ->with($message->getKey(), $schemaDefinition)
            ->willReturn('decoded-key');

        $decoder = new AvroDeserializer($registry, $recordSerializer);

        $result = $decoder->deserialize($message);

        $this->assertInstanceOf(KafkaConsumerMessage::class, $result);
        $this->assertSame('decoded-key', $result->getKey());
        $this->assertSame('body', $result->getBody());
    }

    public function testDeserializeBodyMode()
    {
        $schemaDefinition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $avroSchema = m::mock(KafkaAvroSchemaRegistry::class);
        $avroSchema->expects('getDefinition')->andReturn($schemaDefinition);

        $message = m::mock(KafkaConsumerMessage::class);
        $message->expects('getTopicName')->times(3)->andReturn('test-topic');
        $message->expects('getPartition')->andReturn(0);
        $message->expects('getOffset')->andReturn(1);
        $message->expects('getTimestamp')->andReturn(time());
        $message->expects('getKey')->andReturn('test-key');
        $message->expects('getBody')->twice()->andReturn('body');
        $message->expects('getHeaders')->andReturn([]);

        $registry = m::mock(AvroSchemaRegistry::class);
        $registry->expects('getBodySchemaForTopic')->andReturn($avroSchema);
        $registry->expects('hasBodySchemaForTopic')->andReturn(true);
        $registry->expects('hasKeySchemaForTopic')->andReturn(false);

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
        $registry = m::mock(AvroSchemaRegistry::class);
        $recordSerializer = $this->getMockBuilder(RecordSerializer::class)->disableOriginalConstructor()->getMock();

        $decoder = new AvroDeserializer($registry, $recordSerializer);

        $this->assertSame($registry, $decoder->getRegistry());
    }
}
