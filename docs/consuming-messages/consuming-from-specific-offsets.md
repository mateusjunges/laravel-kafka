---
title: Consuming messages from specific offsets
weight: 3
---

Kafka clients allows you to implement your own partition assignment strategies for consumers, and you can also consume messages from specific offsets.

If you have a topic with multiple consumers and want to assign a consumer to a specific partition offset, you can
use the `assignPartitions` method, available on the `ConsumerBuilder` instance:

```php
$partition = 1; // The partition number you want to assign.
$offset = 0; // The offset you want to start consuming messages from.

$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->assignPartitions([
        new \RdKafka\TopicPartition('your-topic-name', $partition, $offset)
    ]);
```

```+parse
<x-sponsors.request-sponsor/>
```