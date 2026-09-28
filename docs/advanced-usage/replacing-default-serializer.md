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