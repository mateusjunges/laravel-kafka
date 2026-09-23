---
title: Handling failed messages
weight: 6
---

When a message handler throws an exception, the consumer first calls the `failed` method of the consumer class. By default, it rethrows the exception, which is then logged and reported through the Laravel exception handler. What happens to the message next depends on how the consumer is configured.

```+parse
<x-sponsors.request-sponsor/>
```

## Default behavior

Without a dead letter queue, the consumer moves on to the next message and the offset of the failed message is committed. **The failed message is not consumed again**, so the default behavior is at most once delivery for messages whose handler fails.

This is not limited to auto commit mode. Kafka does not acknowledge messages individually: a committed offset is the position a consumer group resumes from in a partition. Skipping the commit of a failed message is not enough to consume it again, because committing any later message of the same partition moves that position past it. With auto commit enabled, librdkafka also commits the offset of every fetched message in the background, whether the handler succeeded or not.

There are two ways to keep failed messages from being lost: sending them to a dead letter queue, or stopping the consumer.

## Sending failed messages to a dead letter queue

When a dead letter queue is configured with `withDlq`, failed messages are published to the dead letter queue topic before their offsets are committed, and the consumer moves on to the next message. See [configuring a dead letter queue](configuring-consumer-options.md) for details.

## Stopping the consumer on failure

If a failed message must be processed before any later message of the same partition, use the `stopOnFailure` method:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->withConsumerGroupId('orders-group')
    ->stopOnFailure()
    ->withHandler(new OrderHandler)
    ->build();

$consumer->consume();
```

When a message fails and there is no dead letter queue, the consumer is closed, leaving the consumer group, and throws a `Junges\Kafka\Exceptions\ConsumerException`. The original exception is available through the `getPrevious` method. The offset of the failed message is not committed, so the next consumer of its partition starts from the failed message. This works both in auto commit mode, where the offsets of the messages processed before the failure are committed when the consumer is closed, and in [manual commit](../advanced-usage/manual-commit.md) mode.

The consumer process is expected to exit, and to be restarted by a process monitor such as Supervisor. Keep in mind that:

- Messages are delivered at least once. A message can be processed again after a restart, so handlers should be idempotent.
- A message that always fails stops the consumer every time it is consumed, blocking its partition until the cause is fixed.
- Make sure your process monitor keeps restarting the consumer. Supervisor, for instance, considers a process that exits within `startsecs` seconds of starting as a failed start, and gives up after `startretries` failed starts.
- When a dead letter queue is also configured, failed messages are sent to it and the consumer does not stop. Stopping only happens for failures that can not be sent anywhere else.

With auto commit enabled, `stopOnFailure` sets the `enable.auto.offset.store` option to `false`, and the consumer stores the offset of each message only after it is processed. This is what keeps librdkafka from committing the offset of a failed message in the background.

Messages consumed by [queueable handlers](queueable-handlers.md) are processed by the queue worker, so their failures are handled by the queue instead.
