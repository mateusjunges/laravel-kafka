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

## Dynamic Offset Assignment

If you need to assign offsets dynamically based on partition assignments (useful when you don't know partition numbers in advance), you can use the `assignPartitionsWithOffsets` method:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['your-topic-name'], 'your-group')
    ->assignPartitionsWithOffsets(function ($partitions) {
        $partitionsWithOffsets = [];
        
        foreach ($partitions as $partition) {
            // Set different offsets based on partition or other logic
            if ($partition->getPartition() === 0) {
                $partition->setOffset(0); // Start from beginning
            } else {
                $partition->setOffset(RD_KAFKA_OFFSET_END); // Start from end
            }
            
            $partitionsWithOffsets[] = $partition;
        }
        
        return $partitionsWithOffsets;
    })
    ->withHandler(function ($message) {
        // Handle message
    });
```

For more information about partition discovery and advanced offset management, see the [Partition Discovery documentation](partition-discovery.md).