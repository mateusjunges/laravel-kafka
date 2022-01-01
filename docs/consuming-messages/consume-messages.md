---
title: Consuming messages
weight: 7
---

When you have finished configuring your consumer, you must call the `build` method, which returns a `Junges\Kafka\Consumers\Consumer` instance.

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('brokers')
    // Configure your consumer here
    ->build();
```

### Consuming the kafka messages
After building the consumer, you must call the `consume` method to consume the messages:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer('brokers')->build();

$consumer->consume();
```
