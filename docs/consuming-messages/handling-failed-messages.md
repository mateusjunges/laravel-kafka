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

Failed messages can be retried before they are handled as failed. After that, there are two ways to keep them from being lost: sending them to a dead letter queue, or stopping the consumer.

## Retrying failed messages

Failures caused by a temporary problem, such as a dependency that is briefly unavailable, can be retried with the `retryFailedMessages` method. It receives the number of retries and, optionally, the time to wait before each retry in milliseconds:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->withConsumerGroupId('orders-group')
    ->retryFailedMessages(3, backoffInMs: 1000)
    ->withDlq()
    ->withHandler(new OrderHandler)
    ->build();
```

When the handler throws an exception, it is called again with the same message, up to the given number of times. Middlewares run again on every attempt. Once all retries are used, the message is handled as failed: the `failed` method of the consumer class is called, and the message is sent to the dead letter queue, stops the consumer, or is skipped, depending on the configuration. Retries also end early when the consumer is asked to stop, for instance by a termination signal.

The consumer waits during the backoff, so no other message is consumed while a message is being retried. Keep the total time spent retrying a message (the number of retries multiplied by the backoff, plus the time the handler takes) well below the `max.poll.interval.ms` consumer option, 5 minutes by default. A consumer that does not poll Kafka within that interval is removed from the consumer group. Longer outages are better handled by a dead letter queue or by stopping the consumer.

The `SeekToCurrentErrorCommitter` committer is deprecated in favor of this method, as it does not make failed messages be consumed again.

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

With auto commit enabled, both `stopOnFailure` and `retryFailedMessages` set the `enable.auto.offset.store` option to `false`, and the consumer stores the offset of each message only after it is processed. This is what keeps librdkafka from committing the offset of a failed message in the background.

Messages consumed by [queueable handlers](queueable-handlers.md) are processed by the queue worker, so their failures are handled by the queue instead.
