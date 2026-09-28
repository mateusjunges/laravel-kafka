---
title: Before and after callbacks
weight: 8
---

The consumer fetches messages from Kafka in a loop, waiting up to the consumer timeout (the `consumer.timeout_ms` key of the connection configuration) for a message on each iteration. The `beforeConsuming` and `afterConsuming` callbacks run before and after each iteration, whether a message was received or not. As an example, you can use them to make your consumer wait while your application is in maintenance mode.

The callbacks get executed in the order they are defined, and they receive the `\Junges\Kafka\Contracts\Consumer` as argument:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['topic'])
    ->beforeConsuming(function (\Junges\Kafka\Contracts\Consumer $consumer) {
        while (app()->isDownForMaintenance()) {
            sleep(1);
        }
    })
    ->afterConsuming(function (\Junges\Kafka\Contracts\Consumer $consumer) {
        // Runs after each iteration, whether a message was consumed or not
    });
```

These callbacks are not middlewares, so you can not interact with the consumed message. To run code for each message, use a [middleware](middlewares.md). You can add as many callbacks as you need, so you can divide different tasks into different callbacks.

```+parse
<x-sponsors.request-sponsor/>
```
