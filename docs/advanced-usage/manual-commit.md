---
title: Manual Commit
weight: 5
---

By default, consumers use auto commit mode. The offset of each message is stored after your handler processes it, and librdkafka commits the stored offsets in the background every `auto.commit.interval.ms`, 5 seconds by default, and when the consumer stops. Failed messages are never committed unless they are sent to a dead letter queue or [skipped on purpose](../consuming-messages/handling-failed-messages.md), so auto commit already gives you at least once delivery.

```+parse
<x-sponsors.request-sponsor/>
```

If the consumer process crashes, the messages processed since the last background commit are consumed again. To make that window shorter, lower the interval:

```php
$consumer = Kafka::consumer(['orders'])
    ->withOption('auto.commit.interval.ms', 1000)
    ->withHandler($handler);
```

With manual commit, the handler decides when offsets are committed. Use it when auto commit does not fit, for instance to:

- Commit a message only after work that completes outside the handler, such as a batch written to a database.
- Commit only after a group of messages was processed.
- Wait for each commit to be acknowledged before handling the next message.

## Enabling manual commit

Call `withManualCommit()` when creating your consumer, and commit from your handler through the consumer it receives:

```php
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer(['orders'])
    ->withManualCommit()
    ->withHandler(function (ConsumerMessage $message, Consumer $consumer) {
        processOrder($message->getBody());

        $consumer->commit($message);
    })
    ->build();

$consumer->consume();
```

If `processOrder` throws, the commit is not reached. The message is then handled as failed: it is [retried](../consuming-messages/handling-failed-messages.md) if the consumer retries failed messages, and then sent to the dead letter queue, skipped, or stops the consumer.

Always let the exception of a failed message propagate. Catching it without rethrowing makes the consumer move on as if the message was processed, and the next commit of the same partition moves past it, so the message is lost.

## Commit methods

The consumer passed to handlers has two commit methods. `commit()` waits until Kafka acknowledged the commit, and `commitAsync()` returns right away:

```php
// Commit the offsets of the current assignment
$consumer->commit();

// Commit the offset of a message
$consumer->commit($message);

// Commit the offsets of specific partitions
$consumer->commit([$topicPartition1, $topicPartition2]);

// Same, without waiting for Kafka to acknowledge the commit
$consumer->commitAsync($message);
```

Both methods accept:

- Nothing, to commit the offsets of the current assignment.
- A `Junges\Kafka\Contracts\ConsumerMessage` or `RdKafka\Message`, to commit the offset right after that message.
- An array of `RdKafka\TopicPartition`, to commit specific offsets.

Asynchronous commits don't throw when they fail. Register an [offset commit callback](../consuming-messages/configuring-consumer-options.md#configuration-callbacks) with `onOffsetCommit()` to find out about failures.

Messages published while handling a message are flushed before each commit, so they are delivered before the consumed message is committed.

## Committing groups of messages

Kafka commits offsets per partition, so committing a message also commits every message before it in the same partition. To commit less often, process every message as it arrives and commit every few messages:

```php
$handled = 0;

$consumer = Kafka::consumer(['page-views'])
    ->withManualCommit()
    ->withHandler(function (ConsumerMessage $message, Consumer $consumer) use (&$handled) {
        recordPageView($message->getBody());

        if (++$handled % 100 === 0) {
            $consumer->commitAsync();
        }
    })
    ->onStopConsuming(function () use (&$consumer) {
        // Commit the messages handled since the last commit
        $consumer->commit();
    })
    ->build();

$consumer->consume();
```

Calling `commit()` or `commitAsync()` without arguments commits the offsets of every assigned partition, up to the last message the consumer received, not only the partition of the last message. With manual commit, nothing is committed when the consumer stops unless you do it, so the example commits in the `onStopConsuming` callback. If the consumer crashes, the messages handled since the last commit are consumed again.

## Dead letter queues

Manual commit works with [dead letter queues](../consuming-messages/configuring-consumer-options.md#configuring-a-dead-letter-queue). A message whose handler throws is sent to the dead letter queue and committed, so the consumer can move on:

```php
$consumer = Kafka::consumer(['orders'])
    ->withManualCommit()
    ->retryFailedMessages(3, backoffInMs: 1000)
    ->withDlq('orders-dlq')
    ->withHandler(function (ConsumerMessage $message, Consumer $consumer) {
        processOrder($message->getBody());

        $consumer->commit($message);
    });
```

## Troubleshooting

**Messages are consumed again after a restart:** the handler did not commit them before the consumer stopped. Make sure every code path that finishes processing a message commits it, or commits a later message of the same partition.

**Failed messages are not consumed again:** the handler caught the exception instead of letting it propagate, and a later commit moved past the message.

**Commits are slow:** use `commitAsync()`, or commit groups of messages instead of every message.
