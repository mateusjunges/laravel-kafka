---
title: Handling message batch
weight: 8
---

If you want to handle multiple messages at once, you can build consumer with batch settings. `enableBatching` enables batching feature.
`withBatchSizeLimit` sets maximum size of a batch.
`withBatchReleaseIntervalInMilliseconds` sets interval between handling batch.
The example below shows that batch is going to be handled if batch size is greater or equals to 1000 or every 1500 milliseconds.
Batching feature could be helpful when you work with databases like ClickHouse, where you insert data in large batches.
```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()
    ->enableBatching()
    ->withBatchSizeLimit(1000)
    ->withBatchReleaseIntervalInMilliseconds(1500)
    ->withHandler(function (\Illuminate\Support\Collection $collection) {
         // Handle batch
    })
    ->build();

$consumer->consume();
```