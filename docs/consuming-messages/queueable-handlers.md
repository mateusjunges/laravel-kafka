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
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class Handler implements HandlerContract, ShouldQueue
{
    public function __invoke(KafkaConsumerMessage $message): void
    {
        // Handle the consumed message.
    }
}
```

As you can see on the `__invoke` method, queued handlers does not have access to a `MessageConsumer` instance when handling the message,
because it's running on a laravel queue and there are no actions that can be performed asynchronously on Kafka message consumer.

After creating your handler class, you can use it just as a normal handler, and `laravel-kafka` will know how to handle it under the hoods 😄.


