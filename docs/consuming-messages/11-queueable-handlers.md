---
title: Queueable handlers
weight: 11
---

Queueable handlers allow you to handle your kafka messages in a queue. This will put a job into the Laravel queue system for each message received by your Kafka consumer.

This only requires you to implements the `Illuminate\Contracts\Queue\ShouldQueue` interface in your Handler.

This is how a queueable handler looks like:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class Handler implements HandlerContract, ShouldQueue
{
    public function __invoke(KafkaConsumerMessage $message): void
    {
        // Handle the consumed message.
    }
}
```

After creating your handler class, you can use it just as a normal handler, and `laravel-kafka` will know how to handle it under the hoods 😄.
