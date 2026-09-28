---
title: Handling failed messages
weight: 6
---

When a message handler throws an exception, the exception is logged and reported through the Laravel exception handler. What happens to the message next depends on how the consumer is configured.

```+parse
<x-sponsors.request-sponsor/>
```

## Default behavior

Without a dead letter queue, the consumer stops when a message fails, and **the offset of the failed message is not committed**. The consumer is closed, leaving the consumer group, and `consume()` throws a `Junges\Kafka\Exceptions\ConsumerException`. The original exception is available through the `getPrevious` method. The next consumer of the partition, including the same consumer once it is restarted, starts from the failed message, so failed messages are not lost.

```php
use Junges\Kafka\Exceptions\ConsumerException;

$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->withGroupId('orders-group')
    ->withHandler(new OrderHandler)
    ->build();

$consumer->consume(); // Throws a ConsumerException when a message fails
```

The consumer process is expected to exit, and to be restarted by a process monitor such as Supervisor. Keep in mind that:

- Messages are delivered at least once. A message can be processed again after a restart, so handlers should be idempotent.
- A message that always fails stops the consumer every time it is consumed, blocking its partition until the cause is fixed. Use a dead letter queue to move such messages out of the way.
- Make sure your process monitor keeps restarting the consumer. Supervisor, for instance, considers a process that exits within `startsecs` seconds of starting as a failed start, and gives up after `startretries` failed starts.

This works both in auto commit mode and in [manual commit](../advanced-usage/manual-commit.md) mode. In auto commit mode, the consumer sets the `enable.auto.offset.store` option to `false` and stores the offset of each message only after it is processed. This keeps librdkafka from committing the offset of a failed message in the background, which it would otherwise do as soon as the message is fetched.

Failed messages can be retried before they are handled as failed. After that, they can also be sent to a dead letter queue, or skipped.

## Retrying failed messages

Failures caused by a temporary problem, such as a dependency that is briefly unavailable, can be retried with the `retryFailedMessages` method. It receives the number of retries and, optionally, the time to wait before each retry in milliseconds:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->withGroupId('orders-group')
    ->retryFailedMessages(3, backoffInMs: 1000)
    ->withDlq()
    ->withHandler(new OrderHandler)
    ->build();
```

When the handler throws an exception, it is called again with the same message, up to the given number of times. Middlewares run again on every attempt. The `getAttempts` method of the message returns how many times the handler was called with it, including the current call:

```php
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Consumer;

function (ConsumerMessage $message, Consumer $consumer) {
    if ($message->getAttempts() > 1) {
        logger()->info('Retrying message', ['offset' => $message->getOffset()]);
    }

    // ...
}
```
 Once all retries are used, the message is handled as failed: the [failure callback](#being-notified-of-failed-messages) is called, and the message is sent to the dead letter queue, stops the consumer, or is skipped, depending on the configuration. Retries also end early when the consumer is asked to stop, for instance by a termination signal.

To wait longer before each retry, pass an array of backoffs instead. Each retry waits for the value at its position, and the last value is used for the remaining retries:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->retryFailedMessages(5, backoffInMs: [1000, 5000, 10000])
    ->withHandler(new OrderHandler)
    ->build();
```

The consumer waits during the backoff, so no other message is consumed while a message is being retried. Keep the total time spent retrying a message (the number of retries multiplied by the backoff, plus the time the handler takes) well below the `max.poll.interval.ms` consumer option, 5 minutes by default. A consumer that does not poll Kafka within that interval is removed from the consumer group. Longer outages are better handled by a dead letter queue or by letting the consumer stop.

## Sending failed messages to a dead letter queue

When a dead letter queue is configured with `withDlq`, failed messages are published to the dead letter queue topic before their offsets are committed, and the consumer moves on to the next message instead of stopping. See [configuring a dead letter queue](configuring-consumer-options.md) for details.

## Skipping failed messages

If losing a failed message is acceptable, use the `skipFailedMessages` method. The consumer then commits the offset of the failed message and moves on to the next one, so **the failed message is not consumed again**:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['page-views'])
    ->withGroupId('analytics')
    ->skipFailedMessages()
    ->withHandler(new PageViewHandler)
    ->build();
```

A `Junges\Kafka\Events\MessageSkipped` event is dispatched for every skipped message, with the message and the exception that made it fail, so you can monitor them:

```php
use Illuminate\Support\Facades\Event;
use Junges\Kafka\Events\MessageSkipped;

Event::listen(function (MessageSkipped $event) {
    logger()->warning('Kafka message skipped', [
        'topic' => $event->message->getTopicName(),
        'offset' => $event->message->getOffset(),
        'exception' => $event->throwable->getMessage(),
    ]);
});
```

When a dead letter queue is also configured, failed messages are sent to it instead of being skipped.

## Being notified of failed messages

To run some code when a message is handled as failed, once its retries are used, override the `failed` method of a [consumer class](class-structure.md):

```php
use Illuminate\Support\Facades\Notification;
use Junges\Kafka\Contracts\ConsumerMessage;
use Throwable;

public function failed(ConsumerMessage $message, Throwable $exception): void
{
    Notification::route('slack', config('services.slack.alerts'))
        ->notify(new KafkaMessageFailed($message, $exception));
}
```

When building a consumer yourself, use the `onMessageFailed` method of the consumer builder:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->onMessageFailed(function (ConsumerMessage $message, Throwable $exception) {
        // ...
    })
    ->withHandler(new OrderHandler)
    ->build();
```

The callback runs before the message is sent to the dead letter queue, skipped, or stops the consumer, and it can't change what happens to it. An exception thrown by the callback is reported, and the message is handled as usual.
