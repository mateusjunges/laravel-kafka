---
title: Graceful shutdown
weight: 2
---

Stopping consumers gracefully ensures you don't kill a process halfway through processing a consumed message.

Consumers listen to the `SIGTERM`, `SIGINT` and `SIGQUIT` signals. When one of them is received, the consumer finishes the message it is handling, stops consuming, and closes, committing the stored offsets and leaving the consumer group. Listening to signals requires the [Process Control extension](https://www.php.net/manual/en/book.pcntl.php).

```+parse
<x-sponsors.request-sponsor/>
```

If the process running the consumer had already registered a handler for one of these signals, for example a Laravel queue worker running a consumer inside a queued job, that handler is still invoked, and the original handlers are restored once `consume()` returns. A queue worker therefore keeps honouring the graceful shutdown of `queue:work` after it has run a consumer.

When a signal arrives while a failed message is being [retried](../consuming-messages/handling-failed-messages.md), the remaining retries are skipped and the message is handled as failed right away.

Process monitors such as Supervisor send a signal and wait for the process to exit before killing it. Make sure they wait longer than the time your handler takes to process a message, see [running consumers in production](running-consumers-in-production.md).

### Running callbacks when the consumer stops
If your app needs to run some code when the consumer stops consuming messages, use the `onStopConsuming` method of the consumer builder. The callback runs when the consumer stops normally, for instance after a signal or once it reached the limits set with `stopAfterMessages` or `stopAfterSeconds`, but not when it stops because of an exception:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer(['topic'])
    ->withHandler(new Handler)
    ->onStopConsuming(static function () {
        // Do something when the consumer stops consuming messages
    })
    ->build();

$consumer->consume();
```

To run code whenever any consumer stops, including when it stops because of an exception, listen to the `ConsumerStopped` [event](events.md). Its `reason` property tells why the consumer stopped.
