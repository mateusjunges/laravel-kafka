---
title: Queueable handlers
weight: 11
---

Queueable handlers allow you to handle your kafka messages in a queue. This will put a job into the Laravel queue system for each message received by your Kafka consumer.

```+parse
<x-sponsors.request-sponsor/>
```

This only requires you to implements the `Illuminate\Contracts\Queue\ShouldQueue` interface in your Handler.

This is how a queueable handler looks like:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Junges\Kafka\Contracts\Handler as HandlerContract;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

class Handler implements HandlerContract, ShouldQueue
{
    public function __invoke(ConsumerMessage $message, ?MessageConsumer $consumer = null): void
    {
        // Handle the consumed message.
        // Note: $consumer will be null for queued handlers
    }
}
```

As you can see on the `__invoke` method, queued handlers receive a `MessageConsumer` parameter, but it will be `null` when the handler is executed in the queue,
because it's running on a laravel queue and there are no actions that can be performed asynchronously on Kafka message consumer.

You can specify which queue connection and queue name to use for your handler by implementing the `onConnection` and `onQueue` methods:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Junges\Kafka\Contracts\Handler as HandlerContract;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

class Handler implements HandlerContract, ShouldQueue
{
    public function __invoke(ConsumerMessage $message, ?MessageConsumer $consumer = null): void
    {
        // Handle the consumed message.
        // Note: $consumer will be null for queued handlers
    }

    public function onConnection(): string
    {
        return 'sqs'; // Specify your queue connection
    }

    public function onQueue(): string
    {
        return 'kafka-handlers'; // Specify your queue name
    }
}
```

After creating your handler class, you can use it just as a normal handler, and `laravel-kafka` will know how to handle it under the hoods 😄.


