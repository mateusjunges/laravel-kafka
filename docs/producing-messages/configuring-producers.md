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

To find out about messages that could not be delivered, listen to the `MessageDeliveryFailed` [event](../advanced-usage/events.md). To be notified of the delivery of every message, register a delivery report callback on the connection with `onDeliveryReport()`.

### Configuration callbacks

librdkafka configuration callbacks, such as the error, log and OAUTHBEARER token refresh callbacks, are registered on the connection. Because the producer is created the first time a message is published, register them before publishing any message, for example in the `boot` method of a service provider:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::connection()
    ->onError(function ($kafka, int $err, string $reason) {
        logger()->error($reason);
    })
    ->onLog(function ($kafka, int $level, string $facility, string $message) {
        logger()->debug($message);
    });
```

Delivery failures are also dispatched as `Junges\Kafka\Events\MessageDeliveryFailed` events, so you usually don't need a delivery report callback, registered with `onDeliveryReport()`, to find out about them.

Callbacks registered on a connection are applied to its producer and to the consumers created using it. Registering a callback after the connection's producer was created throws a `LogicException`, because librdkafka can't change the configuration of an existing producer.

### Transactions

Transactions deliver a group of messages all together, or not at all. To use them, set a `transactional.id` in the producer options of a dedicated connection. Every message published through that connection must then be published inside a transaction:

```php
'connections' => [
    'payments' => [
        'brokers' => env('KAFKA_BROKERS'),
        'producer' => [
            'options' => [
                'transactional.id' => 'payments-'.gethostname().'-'.getmypid(),
            ],
        ],
    ],
],
```

Then, publish the messages inside the `transaction` method of the connection:

```php
use Junges\Kafka\Connection;
use Junges\Kafka\Facades\Kafka;

Kafka::connection('payments')->transaction(function (Connection $connection) {
    $connection->publish('ledger')->withBody(['order_id' => 1, 'amount' => 100])->send();
    $connection->publish('invoices')->withBody(['order_id' => 1])->send();
});
```

The transaction is committed once the callback returns, and the value returned by the callback is returned by `transaction`. If the callback throws, the transaction is aborted, none of its messages are delivered, and the exception is rethrown.

Kafka reports some transaction errors as temporary. When committing fails with a retriable error, the commit is retried, and when Kafka requires the transaction to be aborted, it is aborted and the callback runs again. Both happen up to 3 times, which you can change with the `attempts` argument:

```php
Kafka::connection('payments')->transaction($callback, attempts: 5);
```

As the callback may run more than once, avoid side effects in it other than publishing messages. Kafka allows a single producer at a time for each `transactional.id`: when a producer starts a transaction, older producers using the same id are fenced off and their transactions fail. Every process publishing transactions, such as each PHP-FPM worker or queue worker, must therefore use its own id, which is why the example includes the host name and the process id.

[rdkafka_config]:https://github.com/confluentinc/librdkafka/blob/master/CONFIGURATION.md
