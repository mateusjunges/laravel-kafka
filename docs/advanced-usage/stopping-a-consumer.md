---
title: Stop consumer on demand
weight: 6
---

Sometimes, you may want to stop your consumer based on a given message or any other condition.

You can do it by adding a calling `stopConsuming()` method on the `MessageConsumer` instance that is passed as the 
second argument of your message handler:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['topic'])
    ->withConsumerGroupId('group')
    ->stopAfterLastMessage()
    ->withHandler(static function (\Junges\Kafka\Contracts\ConsumerMessage $message, \Junges\Kafka\Contracts\MessageConsumer $consumer) {
        if ($someCondition) {
            $consumer->stopConsuming();
        }
    })
    ->build();

$consumer->consume();
```

The `onStopConsuming` callback will be executed before stopping your consumer.

```+parse
<x-sponsors.request-sponsor/>
```