---
title: Manual Commit
weight: 5
---

Manual commit gives you complete control over when message offsets are committed to Kafka. This provides stronger processing guarantees and better error handling compared to auto-commit mode.

```+parse
<x-sponsors.request-sponsor/>
```

## Overview

By default, the package uses auto-commit mode where messages are automatically committed after your handler successfully processes them. With manual commit, you decide exactly when to commit messages, allowing for:

- **At-least-once delivery**: Ensure messages are only committed after successful processing
- **Better error handling**: Don't commit messages that failed to process
- **Custom commit strategies**: Implement batch commits, conditional commits, or other patterns
- **Performance optimization**: Use asynchronous commits for better throughput

## Enabling Manual Commit

To enable manual commit, set `withManualCommit()` when creating your consumer:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer(['my-topic'])
    ->withManualCommit() // Disable auto-commit
    ->withHandler(function($message, $consumer) {
        // Your handler with manual commit control
    })
    ->build();
```

## Commit Methods API

Your message handlers receive a `$consumer` parameter with these commit methods:

### Synchronous Commits (Blocking)

```php
// Commit all current assignment offsets
$consumer->commit();

// Commit specific message offset  
$consumer->commit($message);

// Commit specific partition offsets
$consumer->commit([$topicPartition1, $topicPartition2]);
```

### Asynchronous Commits (Non-blocking)

```php
// Commit all current assignment offsets asynchronously
$consumer->commitAsync();

// Commit specific message offset asynchronously
$consumer->commitAsync($message);

// Commit specific partition offsets asynchronously  
$consumer->commitAsync([$topicPartition1, $topicPartition2]);
```

### Parameters

All commit methods accept these parameter types:

- **`null`** (default): Commit offsets for current assignment
- **`ConsumerMessage`**: Commit offset for the specific message
- **`RdKafka\Message`**: Commit offset for the underlying Kafka message
- **`RdKafka\TopicPartition[]`**: Commit specific partition offsets

## Basic Usage Patterns

### Simple Manual Commit

```php
$consumer = Kafka::consumer(['orders'])
    ->withManualCommit()
    ->withHandler(function($message, $consumer) {
        try {
            // Process the order
            $order = json_decode($message->getBody(), true);
            processOrder($order);
            
            // Commit only after successful processing
            $consumer->commit($message);
            
        } catch (Exception $e) {
            Log::error('Order processing failed', [
                'error' => $e->getMessage(),
                'order_id' => $order['id'] ?? 'unknown'
            ]);
        }
    });
```

### Async Commit for Better Performance

```php
$consumer->withHandler(function($message, $consumer) {
    // Process message
    processMessage($message);
    
    // Use async commit for better throughput
    $consumer->commitAsync($message);
    
    // Handler can continue immediately without waiting for commit
});
```

## Error Handling

### Retry Logic with Manual Commit

```php
$consumer->withHandler(function($message, $consumer) {
    $maxRetries = 3;
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            processMessage($message);
            $consumer->commit($message);
            return; // Success
            
        } catch (RetryableException $e) {
            $attempt++;
            Log::warning("Retry attempt {$attempt}", ['error' => $e->getMessage()]);
            
            if ($attempt >= $maxRetries) {
                // Send to DLQ or handle permanent failure
                Log::error('Max retries exceeded', ['error' => $e->getMessage()]);
                throw $e;
            }
            
            sleep(pow(2, $attempt));
            
        } catch (Exception $e) {
            // Non-retryable error
            Log::error('Non-retryable error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
});
```

### Dead Letter Queue Integration

Manual commit works seamlessly with DLQ functionality:

```php
$consumer = Kafka::consumer(['orders'])
    ->withManualCommit()
    ->withDlq('orders-dlq') // Configure DLQ
    ->withHandler(function($message, $consumer) {
        try {
            processOrder($message);
            $consumer->commit($message);
            
        } catch (ValidationException $e) {
            // Don't commit - let DLQ handling take over
            Log::error('Invalid order format', ['error' => $e->getMessage()]);
            throw $e; // This will trigger DLQ
        }
    });
```

## Performance Considerations

### Sync vs Async Commits

- **Synchronous commits**: Slower but guarantees the commit completed
- **Asynchronous commits**: Faster but fire-and-forget

```php
// High-throughput scenario - use async
$consumer->commitAsync($message);

// Critical data - use sync for guarantee
$consumer->commit($message);
```

### Batch Commit Optimization

```php
$consumer->withHandler(function($message, $consumer) {
    static $messages = [];
    
    // Collect messages
    $messages[] = $message;
    
    // Batch commit every 100 messages
    if (count($messages) >= 100) {
        // Process all messages
        foreach ($messages as $msg) {
            processMessage($msg);
        }
        
        // Commit the last message's offset (commits all previous)
        $consumer->commitAsync(end($messages));
        $messages = [];
    }
});
```

## Migration from Auto-Commit

To migrate existing auto-commit consumers to manual commit:

### Before (Auto-commit)
```php
$consumer = Kafka::consumer(['topic'])
    ->withAutoCommit()  // or omit, it's the default
    ->withHandler(function($message, $consumer) {
        processMessage($message);
    });
```

### After (Manual commit)
```php
$consumer = Kafka::consumer(['topic'])
    ->withManualCommit() // Enable manual control
    ->withHandler(function($message, $consumer) {
        try {
            processMessage($message);
            $consumer->commit($message); // Explicit commit
        } catch (Exception $e) {
            // Handle errors without committing
            Log::error('Processing failed', ['error' => $e->getMessage()]);
        }
    });
```

## Best Practices

1. **Always commit after successful processing**: Only commit messages that were fully processed
2. **Use async commits for performance**: Unless you need commit guarantees, use `commitAsync()`
3. **Implement proper error handling**: Don't commit messages that failed to process
4. **Handle duplicate processing**: Manual commit provides at-least-once delivery, so implement idempotent processing

## Troubleshooting

### Common Issues

**Messages being reprocessed repeatedly:**
- Check that you're calling `commit()` after successful processing
- Ensure exceptions don't prevent the commit call
- Verify your error handling doesn't commit failed messages

**Poor performance:**
- Use `commitAsync()` instead of `commit()` for better throughput
- Implement batch commits for high-volume scenarios
- Avoid committing every single message in high-throughput scenarios

**Offset commit errors:**
- Check Kafka broker connectivity
- Verify consumer group permissions
- Monitor Kafka logs for commit-related errors