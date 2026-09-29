---
title: Custom serializers
weight: 4
---

Serialization is the process of converting messages to bytes, and deserialization is the inverse process, converting bytes back into data your application can use. Producers use serializers to prepare messages for transmission, and consumers use deserializers to read them.

```+parse
<x-sponsors.request-sponsor/>
```

This package provides two serializers and deserializers out of the box:

- `JsonSerializer` and `JsonDeserializer`, used by default.
- `AvroSerializer` and `AvroDeserializer`, which use a schema registry.

To create a custom serializer, create a class that implements the `\Junges\Kafka\Contracts\MessageSerializer` contract, which requires a `serialize` method. Then tell the producer to use it with the `usingSerializer` method:

```php
\Junges\Kafka\Facades\Kafka::publish('topic')->usingSerializer(new MyCustomSerializer());
```

To change the serializer used by default, for every connection or for a single one, see [replacing the default serializer](../advanced-usage/replacing-default-serializer.md).

### Using the AVRO serializer
To use the AVRO serializer, create a schema registry and map the schemas of each topic:

```php
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Registry\BlockingRegistry;
use FlixTech\SchemaRegistryApi\Registry\Cache\AvroObjectCacheAdapter;
use FlixTech\SchemaRegistryApi\Registry\CachedRegistry;
use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use GuzzleHttp\Client;
use Junges\Kafka\Message\KafkaAvroSchema;
use Junges\Kafka\Message\Registry\AvroSchemaRegistry;
use Junges\Kafka\Message\Serializers\AvroSerializer;

$cachedRegistry = new CachedRegistry(
    new BlockingRegistry(
        new PromisingRegistry(
            new Client(['base_uri' => 'kafka-schema-registry:9081'])
        )
    ),
    new AvroObjectCacheAdapter()
);

$registry = new AvroSchemaRegistry($cachedRegistry);
$recordSerializer = new RecordSerializer($cachedRegistry);

// If no version is defined, the latest version is used.
// If no schema definition is defined, the appropriate version is fetched from the registry.
$registry->addBodySchemaMappingForTopic(
    'test-topic',
    new KafkaAvroSchema('bodySchemaName' /*, int $version, AvroSchema $definition */)
);
$registry->addKeySchemaMappingForTopic(
    'test-topic',
    new KafkaAvroSchema('keySchemaName' /*, int $version, AvroSchema $definition */)
);

$serializer = new AvroSerializer($registry, $recordSerializer /*, AvroEncoderInterface::ENCODE_BODY */);

\Junges\Kafka\Facades\Kafka::publish('test-topic')->usingSerializer($serializer);
```
