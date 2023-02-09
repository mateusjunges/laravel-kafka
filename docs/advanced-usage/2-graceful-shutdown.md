---
title: Graceful shutdown
weight: 2
---

Stopping consumers is very useful if you want to ensure you don't kill a process halfway through processing a consumed message.

Starting from version `1.12.x` of this package, consumers automatically listen to the `SIGTERM` and `SIQUIT` signals, which means you can easily stop your consumers using those signals.

### Running callbacks when the consumer stops
If your app requires that you run sum sort of processing when the consumers stop processing messages, you can use the `onStopConsume` method, available on the `\Junges\Kafka\Contracts\CanConsumeMessages` interface. This method accepts a `Closure` that will run once your consumer stops consuming. 

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::createConsumer(['topic'])
    ->withConsumerGroupId('group')
    ->withHandler(new Handler)
    ->build()
    ->onStopConsume(static function () {
        // Do something when the consumer stop consuming messages
    })

$consumer->consume();
```

> You will require the [Process Control Extension](https://www.php.net/manual/en/book.pcntl.php) to be installed to utilise this feature.
