---
title: Producing messages
weight: 1
---

To publish your messages to Kafka, use the `publish` method of the `Junges\Kafka\Facades\Kafka` facade, passing the topic the message should be published to:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publish('topic-name')
    ->withBody(['order_id' => 1])
    ->send();
```

This method returns a `Junges\Kafka\Producers\PendingMessage` instance, which you can use to configure the message before sending it.

```+parse
<x-sponsors.request-sponsor/>
```

### Asynchronous publishing

Messages are published asynchronously by default. Calling `send` queues the message on the producer, which delivers it in the background, so your request handlers don't wait for Kafka to acknowledge each message.

Every connection has a single producer, created the first time you publish a message and shared by every message published through that connection. Queued messages are flushed, which means the application waits until Kafka acknowledged them, at the following moments:

- When the application terminates, after the response is sent or the artisan command finishes.
- After each queued job is processed, so messages published by a job are delivered before the queue worker picks the next one.
- Before a consumer stores or commits the offset of a message, so messages published while handling it are delivered before it is committed, and are not lost if the consumer crashes in between.
- When you call `Kafka::flush()`.

Because there is nothing left to handle an exception when the application terminates, delivery failures at that point are reported to your exception handler instead of being thrown. The `Junges\Kafka\Events\CouldNotPublishMessage` event is dispatched as well.

A successful flush means every queued message was handled, not that every message was delivered. A message can still fail on its own, for instance when its topic does not exist or it is not acknowledged within the `message.timeout.ms` producer option. For each of those, a `Junges\Kafka\Events\MessageDeliveryFailed` event is dispatched, with the topic, partition, key, payload, error and id of the message:

```php
use Illuminate\Support\Facades\Event;
use Junges\Kafka\Events\MessageDeliveryFailed;

Event::listen(function (MessageDeliveryFailed $event) {
    logger()->error('Kafka message not delivered', [
        'topic' => $event->topic,
        'id' => $event->getMessageIdentifier(),
        'error' => $event->error,
    ]);
});
```

### Synchronous publishing

If you need to know that a message was delivered before moving on, use the `publishSync` method. The message is flushed as soon as it is sent, and a `Junges\Kafka\Exceptions\CouldNotPublishMessage` exception is thrown if it could not be delivered:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishSync('topic-name')
    ->withBody(['order_id' => 1])
    ->send();
```

### Publishing using another connection

Both methods use the default connection. To publish to another Kafka cluster, use the `connection` method:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::connection('analytics')
    ->publish('page-views')
    ->withBody(['path' => '/'])
    ->send();
```

See the [connections](/advanced-usage/connections) documentation to learn how to configure connections.
