---
title: Creating a kafka consumer
weight: 1
---

If your application needs to read messages from a Kafka topic, you must create a consumer object, subscribe to the appropriate topic and start receiving messages.

To create a consumer using this package you can use the `consumer` method, on Kafka facade:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer();
```

This method also allows you to specify the `topics` it should consume and the consumer `group id`. When no group id is given, the one defined in the connection configuration is used:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::consumer(['topic-1', 'topic-2'], 'group-id');
```

The consumer uses the brokers, authentication and options of the default connection. To consume from another connection, use the `connection` method:

```php
use Junges\Kafka\Facades\Kafka;

$consumer = Kafka::connection('analytics')->consumer(['topic-1']);
```

These methods return a `Junges\Kafka\Consumers\Builder` instance, and you can use it to configure your consumer.

```+parse
<x-sponsors.request-sponsor/>
```