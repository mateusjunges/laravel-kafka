---
title: Stop consumer when there are no messages left
weight: 6
---

Stopping consumers after the last received message is useful if you want to consume all messages from a given
topic and stop your consumer when the last message arrives.

You can do it by adding a call to the `stopWhenEmpty` method when creating your consumer. [Consumer classes](../consuming-messages/class-structure.md) can use the `--stop-when-empty` option of the `kafka:consume` command instead.

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['topic'])
    ->withConsumerGroupId('group')
    ->stopWhenEmpty()
    ->withHandler(new Handler)
    ->build();

$consumer->consume();
```

When consuming a topic with multiple partitions, the consumer stops only after all assigned partitions have been fully read. Reaching the end of a single partition does not stop the consumer while other partitions still have messages to be processed.

For the consumer to detect the end of a partition as soon as it is reached, the `enable.partition.eof` option must be set to `true` in the consumer options. Without it, the consumer stops only when no message is received within the consumer timeout (defined by the `consumer.timeout_ms` key of the connection configuration).

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['topic'])
    ->withConsumerGroupId('group')
    ->withOptions(['enable.partition.eof' => 'true'])
    ->stopWhenEmpty()
    ->withHandler(new Handler)
    ->build();
```

```+parse
<x-sponsors.request-sponsor/>
```