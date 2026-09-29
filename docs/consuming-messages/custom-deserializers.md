---
title: Custom deserializers
weight: 7
---

To create a custom deserializer, you need to create a class that implements the `\Junges\Kafka\Contracts\MessageDeserializer` contract.
This interface requires a `deserialize` method, which receives the consumed message and returns a new message with the deserialized body.

```+parse
<x-sponsors.request-sponsor/>
```

To set the deserializer you want to use, use the `usingDeserializer` method:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()->usingDeserializer(new MyCustomDeserializer());
```

```+parse
<x-docs.tip title="Hot tip!">
    The deserializer class must use the same algorithm as the serializer used to produce this message.
</x-docs.tip>
```


### Using AVRO deserializer
To use the AVRO deserializer on your consumer, add the Avro deserializer:

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

$registry = new \Junges\Kafka\Message\Registry\AvroSchemaRegistry($cachedRegistry);
$recordSerializer = new RecordSerializer($cachedRegistry);

// If no version is defined, the latest version is used.
// If no schema definition is defined, the appropriate version is fetched from the registry.
$registry->addBodySchemaMappingForTopic(
    'test-topic',
    new \Junges\Kafka\Message\KafkaAvroSchema('bodySchema' , 9 /* , AvroSchema $definition */)
);
$registry->addKeySchemaMappingForTopic(
    'test-topic',
    new \Junges\Kafka\Message\KafkaAvroSchema('keySchema' , 9 /* , AvroSchema $definition */)
);

// If you are only decoding the key or the body, pass that mode as the third argument.
// By default, both the key and the body are decoded.
$deserializer = new \Junges\Kafka\Message\Deserializers\AvroDeserializer($registry, $recordSerializer /*, AvroDecoderInterface::DECODE_BODY */);

$consumer = \Junges\Kafka\Facades\Kafka::consumer()->usingDeserializer($deserializer);
```
