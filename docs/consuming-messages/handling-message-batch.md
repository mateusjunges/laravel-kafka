---
title: Handling message batch
weight: 9
---

If you want to handle multiple messages at once, you can build your consumer enabling batch settings. 
The `enableBatching` method enables the batching feature, and you can use `withBatchSizeLimit` to set the maximum size of a batch.
The `withBatchReleaseInterval` sets the interval in which the batch of messages will be released after timer exceeds given interval.

```+parse
<x-sponsors.request-sponsor/>
```

The example below shows that batch is going to be handled if batch size is greater or equals to 1000 or every 1500 milliseconds.
Batching feature could be helpful when you work with databases like ClickHouse, where you insert data in large batches.

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->enableBatching()
    ->withBatchSizeLimit(1000)
    ->withBatchReleaseInterval(1500)
    ->withHandler(function (\Illuminate\Support\Collection $collection, \Junges\Kafka\Contracts\MessageConsumer $consumer) {
         // Handle batch
    })
    ->build();

$consumer->consume();
```