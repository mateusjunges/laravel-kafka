---
title: Creating a kafka consumer
weight: 1
---

If your application needs to read messages from a Kafka topic, you must create a consumer object, subscribe to the appropriate topic and start receiving messages.

To create a consumer using this package you can use the `createConsumer` method, on Kafka facade:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer();
```

This method also allows you to specify the `topics` it should consume, the `broker` and the consumer `group id`:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer(['topic-1', 'topic-2'], 'group-id', 'broker');
```

This method returns a `Junges\Kafka\Consumers\ConsumerBuilder::class` instance, and you can use it to configure your consumer.