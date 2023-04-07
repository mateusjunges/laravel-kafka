---
title: Before callbacks
weight: 8
---

Before consuming any message, you can call any callbacks. For example to wait for
maintenance mode. Callbacks will receive consumer instance as an argument.
The callbacks get executed in the order they are defined:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()
    ->beforeConsuming(function($consumer) {
        while (app()->isDownForMaintenance()) {
            $sleepTimeInSeconds = random_int(1, 5);
            sleep($sleepTimeInSeconds);
        }       
    });
```

You can add as many callback as you need, so you can divide different tasks into 
different callbacks.

If callbacks return `false` then consumer will go to stop phase.
