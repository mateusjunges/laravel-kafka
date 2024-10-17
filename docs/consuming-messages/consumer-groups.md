---
title: Consumer groups
weight: 4
---

Kafka consumers belonging to the same consumer group share a group id. The consumers in a group divides the topic partitions as fairly amongst themselves as possible by establishing that each partition is only consumed by a single consumer from the group.

```+parse
<x-sponsors.request-sponsor/>
```

To attach your consumer to a consumer group, you can use the method `withConsumerGroupId` to specify the consumer group id:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer()->withConsumerGroupId('foo');
```
