---
title: Custom deserializers
weight: 6
---

To create a custom deserializer, you need to create a class that implements the `\Junges\Kafka\Contracts\MessageDeserializer` contract.
This interface force you to declare the `deserialize` method.

To set the deserializer you want to use, use the `usingDeserializer` method:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->usingDeserializer(new MyCustomDeserializer());
```

> NOTE: The deserializer class must use the same algorithm as the serializer used to produce this message.


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

//if no version is defined, latest version will be used
//if no schema definition is defined, the appropriate version will be fetched form the registry
$registry->addBodySchemaMappingForTopic(
    'test-topic',
    new \Junges\Kafka\Message\KafkaAvroSchema('bodySchema' , 9 /* , AvroSchema $definition */)
);
$registry->addKeySchemaMappingForTopic(
    'test-topic',
    new \Junges\Kafka\Message\KafkaAvroSchema('keySchema' , 9 /* , AvroSchema $definition */)
);

// if you are only decoding key or value, you can pass that mode as additional third argument
// per default both key and body will get decoded
$deserializer = new \Junges\Kafka\Message\Deserializers\AvroDeserializer($registry, $recordSerializer /*, AvroDecoderInterface::DECODE_BODY */);

$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->usingDeserializer($deserializer);
```
