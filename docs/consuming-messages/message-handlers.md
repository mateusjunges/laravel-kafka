---
title: Message handlers
weight: 5
---

Now that you have created your kafka consumer, you must create a handler for the messages this consumer receives. By default, a consumer is any `callable`.
You can use an invokable class or a simple callback. Use the `withHandler` method to specify your handler:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer();

// Using callback:
$consumer->withHandler(function(\Junges\Kafka\Contracts\ConsumerMessage $message, \Junges\Kafka\Contracts\MessageConsumer $consumer) {
    // Handle your message here
});
```

Or, using an invokable class:

```php
class Handler
{
    public function __invoke(\Junges\Kafka\Contracts\ConsumerMessage $message, \Junges\Kafka\Contracts\MessageConsumer $consumer) {
        // Handle your message here
    }
}

$consumer = \Junges\Kafka\Facades\Kafka::consumer()->withHandler(new Handler)
```

The `ConsumerMessage` contract gives you some handy methods to get the message properties:

- `getKey()`: Returns the Kafka Message Key
- `getTopicName()`: Returns the topic where the message was published
- `getPartition()`: Returns the kafka partition where the message was published
- `getHeaders()`: Returns the kafka message headers
- `getBody()`: Returns the body of the message
- `getOffset()`: Returns the offset where the message was published


```+parse
<x-sponsors.request-sponsor/>
```

