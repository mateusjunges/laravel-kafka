---
title: Stop consumer on demand
weight: 6
---

Sometimes, you may want to stop your consumer based on a given message or any other condition.

You can do it by calling the `stopConsuming()` method of the `Junges\Kafka\Contracts\Consumer` instance that is passed as the second argument of your message handler:

```php
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;

$consumer = \Junges\Kafka\Facades\Kafka::consumer(['topic'])
    ->withHandler(static function (ConsumerMessage $message, Consumer $consumer) {
        if ($someCondition) {
            $consumer->stopConsuming();
        }
    })
    ->build();

$consumer->consume();
```

The consumer finishes handling the current message before it stops, and the `onStopConsuming` callback is executed before it closes.

```+parse
<x-sponsors.request-sponsor/>
```
