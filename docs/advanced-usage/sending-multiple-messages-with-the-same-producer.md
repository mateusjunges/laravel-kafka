---
title: Sending multiple messages with the same producer
weight: 9
---

Every connection has a single producer, created the first time you publish a message and shared by every message published through that connection afterwards. You don't need to do anything to reuse it:

```php
use Junges\Kafka\Facades\Kafka;

foreach ($orders as $order) {
    Kafka::publish('orders')->withKey((string) $order->id)->withBody($order->toArray())->send();
}
```

The messages are delivered in the background and flushed when the application terminates. If you need to wait until all of them are delivered before moving on, call `Kafka::flush()`.

```+parse
<x-sponsors.request-sponsor/>
```
