---
title: Consuming messages
weight: 8
---

After building the consumer, you must call the `consume` method to consume the messages:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()->build();
```

### Consuming the kafka messages

After building the consumer, you must call the `consume` method to consume the messages:

```php
$consumer->consume();
```

```+parse
<x-sponsors.request-sponsor/>
```