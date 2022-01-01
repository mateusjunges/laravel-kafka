---
title: Producing messages
weight: 1
---

To publish your messages to Kafka, you can use the `publishOn` method, of `Junges\Kafka\Facades\Kafka` class:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('cluster');
```

The cluster is defined in a `clusters` array inside `config/kafka.php`

The `publishOn` method throws a `InvalidArgumentException` if the specified cluster is not defined.

This method returns a `Junges\Kafka\Producers\ProducerBuilder::class` instance, and you can configure your message.

The `ProducerBuilder` class contains a few methods to configure your kafka producer. The following lines describes these methods.
