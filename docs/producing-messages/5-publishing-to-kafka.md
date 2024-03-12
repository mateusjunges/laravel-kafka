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