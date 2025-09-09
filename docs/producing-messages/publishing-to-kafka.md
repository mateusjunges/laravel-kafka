---
title: Publishing to kafka
weight: 5
---

After configuring all your message options, you must use the send method, to send the message to kafka.

```php
use Junges\Kafka\Facades\Kafka;

/** @var \Junges\Kafka\Producers\Builder $producer */
$producer = Kafka::publish('broker')
    ->onTopic('topic')
    ->withConfigOptions(['key' => 'value'])
    ->withKafkaKey('kafka-key')
    ->withHeaders(['header-key' => 'header-value']);

$producer->send();
```

The `publish()` method uses asynchronous publishing for better performance, batching messages and flushing them when the application terminates. 
If you need immediate message flushing, use `publishSync()` instead:

```php
use Junges\Kafka\Facades\Kafka;

// For immediate flush (synchronous)
$producer = Kafka::publishSync('broker')
    ->onTopic('topic')
    ->withKafkaKey('kafka-key');

$producer->send();
```

```+parse
<x-sponsors.request-sponsor/>
```