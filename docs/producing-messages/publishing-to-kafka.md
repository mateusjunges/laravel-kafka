---
title: Publishing to kafka
weight: 5
---

After configuring all your message options, use the `send` method to send the message to Kafka.

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publish('topic')
    ->withKey('kafka-key')
    ->withHeaders(['header-key' => 'header-value'])
    ->withBody(['key' => 'value'])
    ->send();
```

The `publish` method queues the message, and it is delivered in the background. If you need to wait until the message is delivered, use `publishSync` instead:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishSync('topic')
    ->withKey('kafka-key')
    ->withBody(['key' => 'value'])
    ->send();
```

You can also wait for every queued message to be delivered at any moment by calling `Kafka::flush()`.

```+parse
<x-sponsors.request-sponsor/>
```
