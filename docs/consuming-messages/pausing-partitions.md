---
title: Pausing partitions
weight: 9
---

A consumer can stop fetching messages from some of its partitions for a while, without leaving its consumer group, for instance when a service its handler depends on is unavailable. Call the `pause` method of the consumer passed to handlers and callbacks, and `resume` to fetch messages again:

```php
use Junges\Kafka\Contracts\Consumer;

$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->beforeConsuming(function (Consumer $consumer) {
        if (PaymentGateway::isDown()) {
            $consumer->pause();
        } else {
            $consumer->resume();
        }
    })
    ->withHandler(new OrderHandler)
    ->build();
```

```+parse
<x-sponsors.request-sponsor/>
```

Without arguments, both methods apply to every partition assigned to the consumer. To pause or resume only some partitions, pass them as a list of `RdKafka\TopicPartition`:

```php
use RdKafka\TopicPartition;

$consumer->pause([new TopicPartition('orders', 0)]);
```

A paused consumer keeps polling Kafka, waiting up to the consumer timeout for messages each time, so it stays in its consumer group and keeps running its `beforeConsuming` and `afterConsuming` callbacks, which can resume it.

Partitions can only be paused while the consumer is consuming, and pausing does not survive rebalances: a partition that is revoked and assigned again, to this or another consumer, is no longer paused. Use the `onPartitionsAssigned` [callback](partition-discovery.md) to pause partitions again after a rebalance when needed.
