<?php

namespace Junges\Kafka\Tests\Message;

use AvroSchema;
use Junges\Kafka\Contracts\KafkaAvroSchemaRegistry;
use Junges\Kafka\Message\KafkaAvroSchema;
use Junges\Kafka\Tests\LaravelKafkaTestCase;

class KafkaAvroSchemaTest extends LaravelKafkaTestCase
{
    public function testGetters()
    {
        $definition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $schemaName = 'testSchema';
        $version = 9;

        $avroSchema = new KafkaAvroSchema($schemaName, $version, $definition);

        $this->assertEquals($schemaName, $avroSchema->getName());
        $this->assertEquals($version, $avroSchema->getVersion());
        $this->assertEquals($definition, $avroSchema->getDefinition());
    }

    public function testSetters()
    {
        $definition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $schemaName = 'testSchema';

        $avroSchema = new KafkaAvroSchema($schemaName);

        $avroSchema->setDefinition($definition);

        $this->assertEquals($definition, $avroSchema->getDefinition());
    }

    public function testAvroSchemaWithJustName()
    {
        $schemaName = 'testSchema';

        $avroSchema = new KafkaAvroSchema($schemaName);

        $this->assertEquals($schemaName, $avroSchema->getName());
        $this->assertEquals(KafkaAvroSchemaRegistry::LATEST_VERSION, $avroSchema->getVersion());
        $this->assertNull($avroSchema->getDefinition());
    }
}
