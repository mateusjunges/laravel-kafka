---
title: Setting global configurations
weight: 8
---

At this moment, there is no way of setting global configuration for producers/consumers, but you can use laravel `macro` functionality
to achieve that. Here's an example:


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