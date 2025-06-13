<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Message;

use AvroSchema;
use Junges\Kafka\Contracts\KafkaAvroSchemaRegistry;
use Junges\Kafka\Message\KafkaAvroSchema;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;

final class KafkaAvroSchemaTest extends LaravelKafkaTestCase
{
    #[Test]
    public function getters(): void
    {
        $definition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $schemaName = 'testSchema';
        $version = 9;

        $avroSchema = new KafkaAvroSchema($schemaName, $version, $definition);

        $this->assertEquals($schemaName, $avroSchema->getName());
        $this->assertEquals($version, $avroSchema->getVersion());
        $this->assertEquals($definition, $avroSchema->getDefinition());
    }

    #[Test]
    public function setters(): void
    {
        $definition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $schemaName = 'testSchema';

        $avroSchema = new KafkaAvroSchema($schemaName);

        $avroSchema->setDefinition($definition);

        $this->assertEquals($definition, $avroSchema->getDefinition());
    }

    #[Test]
    public function avro_schema_with_just_name(): void
    {
        $schemaName = 'testSchema';

        $avroSchema = new KafkaAvroSchema($schemaName);

        $this->assertEquals($schemaName, $avroSchema->getName());
        $this->assertEquals(KafkaAvroSchemaRegistry::LATEST_VERSION, $avroSchema->getVersion());
        $this->assertNull($avroSchema->getDefinition());
    }
}
