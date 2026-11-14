---
title: Consuming messages
weight: 8
---

After configuring the consumer, call the `build` method to create it, and then the `consume` method to start consuming messages:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['topic'])
    ->withHandler(new Handler)
    ->build();

$consumer->consume();
```

The `consume` method runs until the consumer is stopped, either by a [signal](../advanced-usage/graceful-shutdown.md), by the [handler](../advanced-usage/stopping-a-consumer.md), by one of the limits set with `stopAfterMessages`, `stopAfterSeconds` or `stopWhenEmpty`, or when a message [fails](handling-failed-messages.md) and there is no dead letter queue.

Consumer classes are run with the `kafka:consume` artisan command instead, see [consumer classes](class-structure.md).

```+parse
<x-sponsors.request-sponsor/>
```
