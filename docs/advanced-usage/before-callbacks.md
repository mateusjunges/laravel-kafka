---
title: Before and after callbacks
weight: 8
---

You can call pre-defined callbacks **Before** and **After** consuming messages. As an example, you can use this to make your consumer to wait while in maintenance mode.
The callbacks get executed in the order they are defined, and they receive a `\Junges\Kafka\Contracts\MessageConsumer` as argument:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->beforeConsuming(function(\Junges\Kafka\Contracts\MessageConsumer $consumer) {
        while (app()->isDownForMaintenance()) {
            sleep(1);
        }       
    })
    ->afterConsuming(function (\Junges\Kafka\Contracts\MessageConsumer $consumer) {
        // Runs after consuming the message
    })
```

These callbacks are not middlewares, so you can not interact with the consumed message.
You can add as many callback as you need, so you can divide different tasks into 
different callbacks.

```+parse
<x-sponsors.request-sponsor/>
```