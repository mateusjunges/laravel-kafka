---
title: Replacing the default serializer/deserializer
weight: 1
---

The default Serializer is resolved using the `MessageSerializer` and `MessageDeserializer` contracts. Out of the box, the `Json` serializers are used.

To set the default serializer you can bind the `MessageSerializer` and `MessageDeserializer` contracts to any class which implements this interfaces.

Open your `AppServiceProvider` class and add this lines to the `register` method:

```php
$this->app->bind(\Junges\Kafka\Contracts\MessageSerializer::class, function () {
   return new MyCustomSerializer();
});

$this->app->bind(\Junges\Kafka\Contracts\MessageDeserializer::class, function() {
    return new MyCustomDeserializer();
});
```