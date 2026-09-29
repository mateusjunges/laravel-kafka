---
title: Stop consumer after last messages
weight: 6
---

Stopping consumers after the last received message is useful if you want to consume all messages from a given
topic and stop your consumer when the last message arrives.

You can do it by adding a call to `stopAfterLastMessage` method when creating your consumer:

This is particularly useful when using signal handlers.

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['topic'])
    ->withConsumerGroupId('group')
    ->stopAfterLastMessage()
    ->withHandler(new Handler)
    ->build();

$consumer->consume();
```

When consuming a topic with multiple partitions, the consumer stops only after all assigned partitions have been fully read. Reaching the end of a single partition does not stop the consumer while other partitions still have messages to be processed.

For the consumer to detect the end of a partition as soon as it is reached, the `enable.partition.eof` option must be set to `true` in the consumer options. Without it, the consumer stops only when no message is received within the consumer timeout (defined by the `consumer_timeout_ms` configuration option).

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['topic'])
    ->withConsumerGroupId('group')
    ->withOptions(['enable.partition.eof' => 'true'])
    ->stopAfterLastMessage()
    ->withHandler(new Handler)
    ->build();
```

Timeouts received before the consumer has any partition assigned are ignored, because joining a consumer group may take longer than the consumer timeout. This means a consumer that never gets a partition assigned (for example, when the group has more consumers than the topic has partitions) keeps waiting for one. If that can happen in your setup, use `withMaxTime` to limit how long the consumer runs.

```+parse
<x-sponsors.request-sponsor/>
```