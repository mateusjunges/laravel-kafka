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

If you want to send multiple messages, consider using the batch producer. The default `send` method is recommended for low-throughput systems only, as it 
flushes the producer after every message that is sent.

```+parse
<x-sponsors.request-sponsor/>
```