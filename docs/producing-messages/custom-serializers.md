---
title: Custom serializers
weight: 4
---

Serialization is the process of converting messages to bytes. Deserialization is the inverse process - converting a stream of bytes into and object. In a nutshell, it transforms the content into readable and interpretable information.

Basically, in order to prepare the message for transmission from the producer we use serializers. This package supports three serializers out of the box:

- NullSerializer / NullDeserializer
- JsonSerializer / JsonDeserializer
- AvroSerializer / JsonDeserializer

If the default `JsonSerializer` does not fulfill your needs, you can make use of custom serializers.

To create a custom serializer, you need to create a class that implements the `\Junges\Kafka\Contracts\MessageSerializer` contract. This interface force you to declare the serialize method.

You can inform your producer which serializer should be used with the `usingSerializer` method:

```php
$producer = \Junges\Kafka\Facades\Kafka::publishOn('topic')->usingSerializer(new MyCustomSerializer());
```

To create a custom serializer, you need to create a class that implements the `\Junges\Kafka\Contracts\MessageSerializer` contract.
This interface force you to declare the `serialize` method.

### Using AVRO serializer
To use the AVRO serializer, add the AVRO serializer:

```php
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Registry\CachedRegistry;
use FlixTech\SchemaRegistryApi\Registry\BlockingRegistry;
use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Registry\Cache\AvroObjectCacheAdapter;
use GuzzleHttp\Client;

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

//if no version is defined, latest version will be used
//if no schema definition is defined, the appropriate version will be fetched form the registry
$registry->addBodySchemaMappingForTopic(
    'test-topic',
    new \Junges\Kafka\Message\KafkaAvroSchema('bodySchemaName' /*, int $version, AvroSchema $definition */)
);
$registry->addKeySchemaMappingForTopic(
    'test-topic',
    new \Junges\Kafka\Message\KafkaAvroSchema('keySchemaName' /*, int $version, AvroSchema $definition */)
);

$serializer = new \Junges\Kafka\Message\Serializers\AvroSerializer($registry, $recordSerializer /*, AvroEncoderInterface::ENCODE_BODY */);

$producer = \Junges\Kafka\Facades\Kafka::publishOn('topic')->usingSerializer($serializer);
```