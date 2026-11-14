---
title: Replacing the default serializer/deserializer
weight: 1
---

The default serializer and deserializer are resolved from the service container, using the `MessageSerializer` and `MessageDeserializer` contracts. Out of the box, the JSON serializer and deserializer are used.

```+parse
<x-sponsors.request-sponsor/>
```

To replace them, bind the `MessageSerializer` and `MessageDeserializer` contracts to your own implementations, in the `register` method of a service provider:

```php
$this->app->bind(\Junges\Kafka\Contracts\MessageSerializer::class, function () {
   return new MyCustomSerializer();
});

$this->app->bind(\Junges\Kafka\Contracts\MessageDeserializer::class, function() {
    return new MyCustomDeserializer();
});
```

To use a different serializer or deserializer for a single [connection](connections.md), for instance a cluster where messages are encoded with Avro, set the `producer.serializer` and `consumer.deserializer` keys of the connection instead:

```php
'connections' => [
    'analytics' => [
        'brokers' => env('KAFKA_ANALYTICS_BROKERS'),
        'producer' => [
            'serializer' => \Junges\Kafka\Message\Serializers\AvroSerializer::class,
        ],
        'consumer' => [
            'deserializer' => \Junges\Kafka\Message\Deserializers\AvroDeserializer::class,
        ],
    ],
],
```

Both are resolved from the service container, so bind them with their dependencies, such as the schema registry, in a service provider.
