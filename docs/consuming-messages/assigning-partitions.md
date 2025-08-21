---
title: Assigning consumers to a topic partition
weight: 3
---

Kafka clients allows you to implement your own partition assignment strategies for consumers.

```+parse
<x-sponsors.request-sponsor/>
```

If you have a topic with multiple consumers and want to assign a consumer to a specific partition topic, you can
use the `assignPartitions` method, available on the `ConsumerBuilder` instance:

```php
$partition = 1; // The partition number you want to assign

$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->assignPartitions([
        new \RdKafka\TopicPartition('your-topic-name', $partition)
    ]);
```

The `assignPartitions` method accepts an array of `\RdKafka\TopicPartition` objects. You can assign multiple partitions to the same consumer
by adding more entries to the `assignPartitions` parameter:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer()
    ->assignPartitions([
        new \RdKafka\TopicPartition('your-topic-name', 1),
        new \RdKafka\TopicPartition('your-topic-name', 2),
        new \RdKafka\TopicPartition('your-topic-name', 3)
    ]);
```

## Dynamic Partition Discovery

If you don't know the partition numbers in advance (which is common when using consumer groups), you can use the partition discovery features:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['your-topic-name'], 'your-group')
    ->withPartitionAssignmentCallback(function ($partitions) {
        echo "Assigned " . count($partitions) . " partitions\n";
        
        foreach ($partitions as $partition) {
            echo "Partition: {$partition->getPartition()}\n";
        }
    })
    ->withHandler(function ($message) {
        // Handle message
    });
```

For more advanced partition discovery and dynamic offset assignment, see the [Partition Discovery documentation](partition-discovery.md).