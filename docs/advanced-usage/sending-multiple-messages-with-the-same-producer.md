---
title: Sending multiple messages with the same producer
weight: 9
---

Sometimes you may want to send multiple messages without having to create the consumer


```php
// In a service provider:

\Junges\Kafka\Facades\Kafka::macro('myProducer', function () {
    return $this->publish('broker')
        ->onTopic('my-awesome-topic')
        ->withConfigOption('key', 'value');
});
```

Now, you can call `\Junges\Kafka\Facades\Kafka::myProducer()`, which will always apply the configs you defined in your service provider.


```+parse
<x-sponsors.request-sponsor/>
```