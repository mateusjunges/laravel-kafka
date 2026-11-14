---
title: Configuring message payload
weight: 3
---

A Kafka message has a body, headers and a key. All of them can be configured on the pending message returned by the `publish` method, and sent with the `send` method.

```+parse
<x-sponsors.request-sponsor/>
```

### Configuring the message body
Use the `withBody` method to set the body of the message. With the default JSON serializer, arrays are encoded as JSON:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publish('orders')
    ->withBody(['id' => 1, 'status' => 'paid', 'paid_at' => now()->toIso8601String()])
    ->send();
```

### Configuring message headers
Use the `withHeaders` method to set all the headers, and the `withHeader` method to set a single one:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publish('orders')
    ->withHeaders(['source' => 'checkout'])
    ->withHeader('tenant', 'acme')
    ->withBody(['id' => 1])
    ->send();
```

### Using Kafka keys
In Kafka, keys determine the partition a message is appended to. Messages with the same key are appended to the same partition, so they are consumed in the order they were published. Use the `withKey` method to set the key of your message:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publish('orders')
    ->withKey((string) $order->id)
    ->withBody($order->toArray())
    ->send();
```

### Using message objects
The `withMessage` method sets the entire message, and it accepts a `Junges\Kafka\Message\Message` instance as argument. The key, headers and body set with the other methods are applied on top of it, whether they are called before or after `withMessage`, and the given message itself is not modified:

```php
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

$message = new Message(
    headers: ['header-key' => 'header-value'],
    body: ['key' => 'value'],
    key: 'kafka key here',
);

Kafka::publish('topic')->withMessage($message)->send();
```

### Message ids
Every message gets a unique id when it is created, stored in the `laravel-kafka::message-id` header. The id stays the same for the whole life of the message: it is sent to Kafka, and it is available in the [events](../advanced-usage/events.md) dispatched while the message is published and consumed. Use the `getMessageIdentifier` method to get it:

```php
use Junges\Kafka\Facades\Kafka;

$pending = Kafka::publish('orders')->withBody(['id' => 1]);

$id = $pending->getMessage()->getMessageIdentifier();

$pending->send();
```

To use your own id, for instance one that already identifies the operation in your application, set the header yourself:

```php
Kafka::publish('orders')
    ->withHeader('laravel-kafka::message-id', $operationId)
    ->withBody(['id' => 1])
    ->send();
```

Consumed messages keep the id they were published with. When a consumed message has no id, for instance because it was published by another application, the consumer gives it a new one. The name of the header can be changed with the `message_id_key` key of the `config/kafka.php` file, or the `MESSAGE_ID_KEY` environment variable.
