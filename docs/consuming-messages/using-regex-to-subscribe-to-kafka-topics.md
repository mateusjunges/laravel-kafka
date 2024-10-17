---
title: Using regex to subscribe to kafka topics
weight: 2
---

Kafka allows you to subscribe to topics using regex, and regex pattern matching is automatically performed for topics prefixed with `^` (e.g. `^myPfx[0-9]_.*`).

```+parse
<x-sponsors.request-sponsor/>
```
 
The consumer will see the new topics on its next periodic metadata refresh which is controlled by the `topic.metadata.refresh.interval.ms` 

To subscribe to topics using regex, you can simply pass the regex you want to use to the `subscribe` method:

```php
\Junges\Kafka\Facades\Kafka::consumer()
    ->subscribe('^myPfx_.*')
    ->withHandler(...)
```

This pattern will match any topics that starts with `myPfx_`.