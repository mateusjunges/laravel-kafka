---
title: Upgrade Guide
weight: 6
---

### Upgrading from `v1.11.x` to `v1.12.x`

### High impact changes
- Renamed method `stopConsume` to `stopConsuming`.
- A new `onStopConsuming` method was added to the `\Junges\Kafka\Contracts\CanConsumeMessages` contract.
- The former `stopConsume` method (now `stopConsuming`) doesn't accept a callback anymore. Instead, you must use the newly added `onStopConsuming` method to specify the callback that should run when the consumer stops consuming messages.

```php
// Laravel Kafka v1.11.x:
$stoppableConsumer = Kafka::createConsumer(['topic'])
    ->withConsumerGroupId('group')
    ->withHandler(function ($message) use ($stoppableConsumer) {
        // Handle the message
        if ($shouldStopConsuming) {
            $stoppableConsumer->stopConsume(function () {
                // Run this callback when stop consuming
            })
        }
    })
    ->build();

// In Laravel Kafka v1.12.x:
$stoppableConsumer = Kafka::createConsumer(['topic'])
    ->withConsumerGroupId('group')
    ->withHandler(function ($message) use ($stoppableConsumer) {
        // Handle the message
    })
    ->build()
    ->onStopConsuming(function () {
        // This will run when the consumer stops consuming messages.
    })
```
