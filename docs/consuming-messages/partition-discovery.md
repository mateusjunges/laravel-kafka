---
title: Partition Discovery and Dynamic Assignment
weight: 4
---

The Laravel Kafka package provides several methods to discover and work with partition assignments dynamically, which is especially useful when you need to set specific offsets but don't know the partition numbers in advance.

```+parse
<x-sponsors.request-sponsor/>
```

## Getting Assigned Partitions

After starting a consumer, you can retrieve the current partition assignment:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['my-topic'], 'my-group')
    ->withHandler(function ($message) {
        // Handle message
    })
    ->build();

// Get the assigned partitions (returns array of RdKafka\TopicPartition objects)
$assignedPartitions = $consumer->getAssignedPartitions();

foreach ($assignedPartitions as $partition) {
    echo "Topic: {$partition->getTopic()}, Partition: {$partition->getPartition()}\n";
}
```

**Note:** `getAssignedPartitions()` returns an empty array until the consumer has been initialized and partitions have been assigned by the broker.

## Partition Assignment Callbacks

You can set a callback that gets executed whenever partitions are assigned to your consumer:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['my-topic'], 'my-group')
    ->withPartitionAssignmentCallback(function ($partitions) {
        echo "Assigned " . count($partitions) . " partitions:\n";
        
        foreach ($partitions as $partition) {
            echo "- Topic: {$partition->getTopic()}, Partition: {$partition->getPartition()}\n";
        }
    })
    ->withHandler(function ($message) {
        // Handle message
    });
```

This callback is particularly useful for:
- Logging partition assignments for debugging
- Initializing partition-specific resources
- Tracking partition assignment changes during rebalancing

## Dynamic Partition Assignment with Offsets

The most powerful feature is the ability to dynamically assign offsets based on discovered partitions:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['my-topic'], 'my-group')
    ->assignPartitionsWithOffsets(function ($partitions) {
        $partitionsWithOffsets = [];
        
        foreach ($partitions as $partition) {
            // Set different offset strategies based on partition
            if ($partition->getPartition() === 0) {
                // Start from the beginning for partition 0
                $partition->setOffset(RD_KAFKA_OFFSET_BEGINNING);
            } elseif ($partition->getPartition() === 1) {
                // Start from the end for partition 1
                $partition->setOffset(RD_KAFKA_OFFSET_END);
            } else {
                // Start from a specific offset for other partitions
                $partition->setOffset(1000);
            }
            
            $partitionsWithOffsets[] = $partition;
        }
        
        return $partitionsWithOffsets;
    })
    ->withHandler(function ($message) {
        // Handle message
    });
```

## Practical Examples

### Resume from Stored Offsets

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['user-events'], 'analytics-group')
    ->assignPartitionsWithOffsets(function ($partitions) {
        $partitionsWithOffsets = [];
        
        foreach ($partitions as $partition) {
            // Get stored offset from database or cache
            $storedOffset = Cache::get("kafka_offset_{$partition->getTopic()}_{$partition->getPartition()}", 0);
            
            $partition->setOffset($storedOffset);
            $partitionsWithOffsets[] = $partition;
            
            Log::info("Resuming from offset {$storedOffset} for partition {$partition->getPartition()}");
        }
        
        return $partitionsWithOffsets;
    })
    ->withHandler(function ($message) {
        // Handle message
        
        // Store current offset
        Cache::put("kafka_offset_{$message->getTopicName()}_{$message->getPartition()}", $message->getOffset() + 1);
    });
```

### Time-based Offset Discovery

```php
use RdKafka\KafkaConsumer;

$consumer = \Junges\Kafka\Facades\Kafka::consumer(['transactions'], 'payment-processor')
    ->assignPartitionsWithOffsets(function ($partitions) {
        $partitionsWithOffsets = [];
        
        // Target timestamp (e.g., start of today)
        $targetTimestamp = strtotime('today') * 1000;
        
        foreach ($partitions as $partition) {
            // You would typically use the low-level consumer to find offsets by timestamp
            // This is a simplified example
            $partition->setOffset(RD_KAFKA_OFFSET_BEGINNING);
            $partitionsWithOffsets[] = $partition;
        }
        
        return $partitionsWithOffsets;
    })
    ->withHandler(function ($message) {
        processTransaction($message);
    });
```

### Partition-Specific Processing

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'], 'order-processor')
    ->withPartitionAssignmentCallback(function ($partitions) {
        // Initialize partition-specific resources
        foreach ($partitions as $partition) {
            $partitionId = $partition->getPartition();
            
            // Each partition might handle different regions
            initializeRegionProcessor($partitionId);
            
            Log::info("Initialized processor for region partition {$partitionId}");
        }
    })
    ->withHandler(function ($message) {
        $partitionId = $message->getPartition();
        processOrderForRegion($message, $partitionId);
    });
```

## Combining with Manual Assignment

You can also combine dynamic discovery with manual partition assignment:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['my-topic'], 'my-group')
    ->assignPartitions([
        new \RdKafka\TopicPartition('my-topic', 0, 100),  // Start from offset 100
        new \RdKafka\TopicPartition('my-topic', 1, RD_KAFKA_OFFSET_END),  // Start from end
    ])
    ->withHandler(function ($message) {
        // Handle message
    });

// Later, you can still get the current assignment
$consumer = $consumer->build();
$partitions = $consumer->getAssignedPartitions();
```

## Important Notes

1. **Timing**: Partition assignments happen during consumer group rebalancing, which occurs when consumers join or leave the group.

2. **Consumer Groups**: If you're using consumer groups, partition assignments are managed by Kafka's partition assignment strategy. Manual assignments override consumer group behavior.

3. **Rebalancing**: When using `withPartitionAssignmentCallback()` or `assignPartitionsWithOffsets()`, your callbacks will be called every time a rebalance occurs.

4. **Error Handling**: Always handle potential errors in your callbacks, as exceptions can disrupt the rebalancing process.

5. **Performance**: Partition assignment callbacks should be fast, as they block the rebalancing process.

This partition discovery functionality solves the common problem of needing to know partition numbers before consumption starts, making it much easier to implement features like offset management, partition-aware processing, and resumable consumers.