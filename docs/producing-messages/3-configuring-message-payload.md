---
title: Configuring message payload
weight: 3
---

In kafka, you can configure your payload with a message, message headers and message key. All these configurations are available within ProducerBuilder class.

### Configuring message headers
To configure the message headers, use the `withHeaders` method:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('topic')
    ->withHeaders([
        'header-key' => 'header-value'
    ])
```

### Configure the message body
You can configure the message with the `withMessage` or `withBodyKey` methods.

The `withMessage` sets the entire message, and it accepts a `Junges\Kafka\Message\Message::class` instance as argument.

This is how you should use it:

```php
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

$message = new Message(
    headers: ['header-key' => 'header-value'],
    body: ['key' => 'value'],
    key: 'kafka key here'  
)

Kafka::publishOn('topic')->withMessage($message);
```

The `withBodyKey` method sets only a key in your message.

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('topic')->withBodyKey('key', 'value');
```

### Using Kafka Keys
In Kafka, keys are used to determine the partition within a log to which a message get's appended to.
If you want to use a key in your message, you should use the `withKafkaKey` method:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::publishOn('topic')->withKafkaKey('your-kafka-key');
```