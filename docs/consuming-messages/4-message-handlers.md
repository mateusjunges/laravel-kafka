---
title: Message handlers
weight: 4
---

Now that you have created your kafka consumer, you must create a handler for the messages this consumer receives. By default, a consumer is any `callable`.
You can use an invokable class or a simple callback. Use the `withHandler` method to specify your handler:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer();

// Using callback:
$consumer->withHandler(function(\Junges\Kafka\Contracts\KafkaConsumerMessage $message) {
    // Handle your message here
});
```

Or, using a invokable class:

```php
class Handler
{
    public function __invoke(\Junges\Kafka\Contracts\KafkaConsumerMessage $message){
        // Handle your message here
    }
}

$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()->withHandler(new Handler)
```

The `KafkaConsumerMessage` contract gives you some handy methods to get the message properties:

- `getKey()`: Returns the Kafka Message Key
- `getTopicName()`: Returns the topic where the message was published
- `getPartition()`: Returns the kafka partition where the message was published
- `getHeaders()`: Returns the kafka message headers
- `getBody()`: Returns the body of the message
- `getOffset()`: Returns the offset where the message was published

