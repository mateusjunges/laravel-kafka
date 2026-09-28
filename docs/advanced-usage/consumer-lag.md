---
title: Consumer lag
weight: 11
---

The lag of a consumer group is the number of messages published to its topics that it has not consumed yet. A lag that keeps growing means its consumers can't keep up, or are not running. Use the `lag` method of a connection to get the lag of a consumer group on each partition of the given topics:

```php
use Junges\Kafka\Facades\Kafka;

$partitions = Kafka::connection()->lag('orders-group', ['orders']);

foreach ($partitions as $partition) {
    logger()->info("Partition {$partition->partition} of {$partition->topic} is {$partition->lag} messages behind.");
}
```

```+parse
<x-sponsors.request-sponsor/>
```

It returns a list of `Junges\Kafka\Consumers\PartitionLag`, sorted by topic and partition, with the following properties:

| Property | Description |
| --- | --- |
| `topic` | The topic. |
| `partition` | The partition. |
| `committedOffset` | The offset of the next message the group consumes from the partition, or `null` when the group never committed an offset for it. |
| `lowWatermark` | The offset of the oldest message still available in the partition. |
| `highWatermark` | The offset the next message published to the partition gets. |
| `lag` | How many messages the group has not consumed yet, or `null` when it never committed an offset for the partition. Where such a group starts reading depends on its `auto.offset.reset` option. |

The lag is read from the offsets the group committed, so it does not count the messages processed since the last commit. With auto commit, offsets are committed every `auto.commit.interval.ms`, 5 seconds by default.

The method does not join the consumer group, so it doesn't trigger a rebalance, and it works whether the consumers of the group are running or not. It queries the brokers for each partition, waiting up to the timeout given as its third argument, 10 seconds by default, for each request. Running it from a scheduled command lets you keep track of the lag over time, and alert when it grows.

While consuming, each consumer can also report the lag of its own partitions through the `StatisticsReported` [event](events.md), once the `statistics.interval.ms` option is set.
