---
title: Consuming messages
weight: 7
---

After building the consumer, you must call the `consume` method to consume the messages:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->build();
```

### Consuming the kafka messages

After building the consumer, you must call the `consume` method to consume the messages:

```php
$consumer->consume();
```