---
title: Connections
weight: 1
---

A connection holds everything needed to talk to a Kafka cluster: the brokers, the authentication settings and the librdkafka options used by producers and consumers. Connections are defined in the `connections` key of your `config/kafka.php` file, and the `default` key defines which one is used when you don't specify one.

```php
'default' => env('KAFKA_CONNECTION', 'default'),

'connections' => [
    'default' => [
        'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),
        'security_protocol' => env('KAFKA_SECURITY_PROTOCOL', 'PLAINTEXT'),
        // ...
    ],

    'analytics' => [
        'brokers' => env('KAFKA_ANALYTICS_BROKERS'),
        'security_protocol' => 'SASL_SSL',
        'sasl' => [
            'mechanism' => 'SCRAM-SHA-512',
            'username' => env('KAFKA_ANALYTICS_USERNAME'),
            'password' => env('KAFKA_ANALYTICS_PASSWORD'),
        ],
        'producer' => [
            'options' => ['enable.idempotence' => true],
        ],
        'consumer' => [
            'group_id' => 'analytics',
        ],
    ],
],
```

```+parse
<x-sponsors.request-sponsor/>
```

### Using a connection

The `publish`, `publishSync` and `consumer` methods of the `Kafka` facade use the default connection. To use another one, call them on the connection returned by the `connection` method:

```php
use Junges\Kafka\Facades\Kafka;

Kafka::connection('analytics')->publish('page-views')->withBody(['path' => '/'])->send();

Kafka::connection('analytics')->consumer(['page-views'])
    ->withHandler(new PageViewHandler)
    ->build()
    ->consume();
```

[Consumer classes](/consuming-messages/class-structure) use the connection returned by their `connection` method.

### Configuration reference

| Key | Description |
| --- | --- |
| `brokers` | A comma separated list of brokers. |
| `security_protocol` | The security protocol: `PLAINTEXT`, `SSL`, `SASL_PLAINTEXT` or `SASL_SSL`. |
| `sasl` | The SASL `mechanism`, `username` and `password`. SASL is used when a username is set, with the `SASL_SSL` protocol when `security_protocol` is `SSL` or `SASL_SSL`, and `SASL_PLAINTEXT` otherwise. |
| `options` | librdkafka options applied to both producers and consumers. |
| `producer.options` | librdkafka options applied only to the producer. |
| `producer.serializer` | The class serializing the published messages, resolved from the service container. Defaults to the `MessageSerializer` binding, the JSON serializer. |
| `producer.flush_timeout_ms` | How long to wait for queued messages to be delivered when flushing the producer. |
| `producer.flush_retries` | How many times to retry flushing before giving up. |
| `producer.flush_retry_sleep_ms` | How long to wait between flush retries. |
| `consumer.group_id` | The default consumer group id. |
| `consumer.auto_commit` | Whether consumers commit offsets automatically after handling each message. |
| `consumer.timeout_ms` | How long the consumer waits for a message before polling again. |
| `consumer.options` | librdkafka options applied only to consumers. |
| `consumer.deserializer` | The class deserializing the consumed messages, resolved from the service container. Defaults to the `MessageDeserializer` binding, the JSON deserializer. |

The consumer builder methods, such as `withSasl`, `withOptions` and `withAutoCommit`, can still be used to override the connection configuration for a single consumer.
