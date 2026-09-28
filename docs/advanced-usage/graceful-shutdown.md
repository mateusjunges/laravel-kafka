---
title: Graceful shutdown
weight: 2
---

Stopping consumers is very useful if you want to ensure you don't kill a process halfway through processing a consumed message.

Consumers automatically listen to the `SIGTERM`, `SIGINT` and `SIGQUIT` signals, which means you can easily stop your consumers using those signals.

If the process running the consumer had already registered a handler for one of these signals — for example a Laravel queue worker running a consumer inside a queued job — that handler is still invoked, and the original handlers are restored once `consume()` returns. A queue worker therefore keeps honouring the graceful shutdown of `queue:work` after it has run a consumer.

```+parse
<x-sponsors.request-sponsor/>
```

### Running callbacks when the consumer stops
If your app requires that you run sum sort of processing when the consumers stop processing messages, you can use the `onStopConsume` method, available on the `\Junges\Kafka\Contracts\CanConsumeMessages` interface. This method accepts a `Closure` that will run once your consumer stops consuming.

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer(['topic'])
    ->withConsumerGroupId('group')
    ->withHandler(new Handler)
    ->onStopConsuming(static function () {
        // Do something when the consumer stop consuming messages
    })
    ->build();

$consumer->consume();
```

> This features requires [&nbsp;Process Control Extension&nbsp;](https://www.php.net/manual/en/book.pcntl.php) to be installed.
