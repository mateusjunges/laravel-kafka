---
title: Graceful shutdown
weight: 2
---

Stopping consumers is very useful if you want to ensure you don't kill a process halfway through processing a consumed message.

To stop the consumer gracefully call the `stopConsume` method on a consumer instance.

This is particularly useful when using signal handlers.

```php
function gracefulShutdown(Consumer $consumer) {
    $consumer->stopConsume(function() {
        echo 'Stopped consuming';
        exit(0);
    });
}

$consumer = Kafka::createConsumer(['topic'])
    ->withConsumerGroupId('group')
    ->withHandler(new Handler)
    ->build();
    
pcntl_signal(SIGINT, fn() => gracefulShutdown($consumer));

$consumer->consume();
```

> NOTE: You will require the [Process Control Extension](https://www.php.net/manual/en/book.pcntl.php) to be installed to utilise the `pcntl` methods.