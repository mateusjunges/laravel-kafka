---
title: Configuring your kafka producer
weight: 2
---

Each connection has a single producer, shared by every message published through that connection. The producer is configured in the connection configuration, in your `config/kafka.php` file.

```+parse
<x-sponsors.request-sponsor/>
```

### Defining configuration options

The `producer.options` key of a connection accepts any librdkafka option. You can check all available options [here][rdkafka_config]. Options defined in the `options` key of the connection are applied to both producers and consumers.

```php
'connections' => [
    'default' => [
        'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),

        'options' => [
            'client.id' => 'my-application',
        ],

        'producer' => [
            'options' => [
                'compression.codec' => 'snappy',
                'enable.idempotence' => true,
                'linger.ms' => 5,
            ],
        ],
    ],
],
```

To enable debug mode while developing your application, set the `debug` option:

```php
'options' => [
    'debug' => 'all',
],
```

### Flushing

The `flush_timeout_ms`, `flush_retries` and `flush_retry_sleep_ms` keys of the `producer` configuration define how long to wait for queued messages to be delivered when flushing the producer, and how many times to retry before giving up.

If you need to know which messages were delivered by each flush, register a flush callback on the connection producer. The callback receives the delivered messages:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::connection()->producer()->withFlushCallback(function (array $messages) {
    // ...
});
```

### Configuration callbacks

librdkafka configuration callbacks, such as the error, log and OAUTHBEARER token refresh callbacks, are registered on the connection. Because the producer is created the first time a message is published, register them before publishing any message, for example in the `boot` method of a service provider:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::connection()
    ->withErrorCb(function ($kafka, int $err, string $reason) {
        logger()->error($reason);
    })
    ->withLogCb(function ($kafka, int $level, string $facility, string $message) {
        logger()->debug($message);
    });
```

Callbacks registered on a connection are applied to its producer and to the consumers created using it. Registering a callback after the connection's producer was created throws a `LogicException`, because librdkafka can't change the configuration of an existing producer.

### Transactions

To use transactions, set a `transactional.id` in the producer options of a dedicated connection, and use its producer directly:

```php
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

$producer = Kafka::connection('transactional')->producer();

$producer->beginTransaction();

$producer->produce(Message::create('orders')->withBody(['order_id' => 1]));
$producer->produce(Message::create('invoices')->withBody(['order_id' => 1]));

$producer->commitTransaction();
```

[rdkafka_config]:https://github.com/confluentinc/librdkafka/blob/master/CONFIGURATION.md
