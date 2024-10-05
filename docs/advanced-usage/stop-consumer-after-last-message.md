---
title: Stop consumer after last messages
weight: 6
---

Stopping consumers after the last received message is useful if you want to consume all messages from a given
topic and stop your consumer when the last message arrives.

You can do it by adding a call to `stopAfterLastMessage` method when creating your consumer:

This is particularly useful when using signal handlers.

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer(['topic'])
    ->withConsumerGroupId('group')
    ->stopAfterLastMessage()
    ->withHandler(new Handler)
    ->build();

$consumer->consume();
```