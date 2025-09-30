---
title: Producing messages
weight: 1
---

To publish your messages to Kafka, you can use the `publish` method, of `Junges\Kafka\Facades\Kafka` class:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publish('broker')->onTopic('topic-name')
```

This method returns a `ProducerBuilder` instance, which contains a few methods to configure your kafka producer. 
The following lines describes these methods.

The default `publish()` method now uses asynchronous publishing for better performance. Messages are queued and flushed when the application terminates:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publish('broker')->onTopic('topic-name')
```

The async producer is a singleton and will only flush messages when the application is shutting down, instead of after each send. 
This reduces overhead when you want to send a lot of messages in your request handlers.

If you need immediate message flushing (synchronous publishing), use the `publishSync()` method:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishSync('broker')->onTopic('topic-name')
```

```+parse
<x-sponsors.request-sponsor/>
```

When doing async publishing, the builder is stored in memory during the entire request. If you need to use a fresh producer, you may use the `fresh` method
available on the `Kafka` facade (added in v2.2.0). This method will return a fresh Kafka Manager, which you can use to produce messages with a newly created producer builder.


```php
use Junges\Kafka\Facades\Kafka;

Kafka::fresh()
    ->publish('broker')
    ->onTopic('topic-name')
```