---
title: Creating a kafka consumer
weight: 1
---

If your application needs to read messages from a Kafka topic, you must create a consumer object, subscribe to the appropriate topic
and start receiving messages.

To create a consumer using this package, you have two options available:

the `createConsumer` and `consumeUsing` methods, on Kafka facade:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer('brokers');
```

This method also allows you to specify the `topics` it should consume and the consumer `group id`:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer('broker', ['topic-1', 'topic-2'], 'group-id');
```

When using the `consumeUsing` method, you must define your `consumer` configuration options within the `consumers` array,
in the `config/kafka.php` configuration file. This method accept a `consumer` name defined there, and returns
a `ConsumerBuilder` instance, which you can configure later.

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumeUsing('consumer');
```

This method returns a `Junges\Kafka\Consumers\ConsumerBuilder::class` instance, and you can use it to configure your consumer.
