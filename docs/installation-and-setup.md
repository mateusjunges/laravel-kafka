---
title: Installation and Setup
weight: 3
---

You can install this package using composer:

```bash
composer require mateusjunges/laravel-kafka
```

The package works with its default configuration, reading the brokers and credentials from environment variables such as `KAFKA_BROKERS`. To customize it, for instance to add [connections](/advanced-usage/connections), publish the configuration file:

```bash
php artisan vendor:publish --tag=laravel-kafka-config
```

```+parse
<x-sponsors.request-sponsor/>
```

This is the default content of the configuration file. Each entry of the `connections` key points to a Kafka cluster, see the [connections](/advanced-usage/connections) documentation for details.

```php
<?php declare(strict_types=1);

return [
    /*
     | The connection used when none is specified, for example by Kafka::publish() or Kafka::consumer().
     */
    'default' => env('KAFKA_CONNECTION', 'default'),

    /*
     | Each connection points to a Kafka cluster. Use Kafka::connection('name') to publish or consume
     | using a connection other than the default one. Every connection has a single producer, which
     | is created on first use and shared by every message published through it.
     */
    'connections' => [
        'default' => [
            'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),

            'security_protocol' => env('KAFKA_SECURITY_PROTOCOL', 'PLAINTEXT'),

            /*
             | SASL is used when a username is set and the security protocol is SASL_PLAINTEXT or SASL_SSL.
             */
            'sasl' => [
                'mechanisms' => env('KAFKA_MECHANISMS', 'PLAIN'),
                'username' => env('KAFKA_USERNAME'),
                'password' => env('KAFKA_PASSWORD'),
            ],

            /*
             | librdkafka options applied to both producers and consumers. See the list of available options at
             | https://github.com/confluentinc/librdkafka/blob/master/CONFIGURATION.md
             */
            'options' => [],

            'producer' => [
                /*
                 | How long to wait for queued messages to be delivered when flushing the producer, and how
                 | many times to retry before giving up.
                 */
                'flush_timeout_ms' => 1000,
                'flush_retries' => 10,
                'flush_retry_sleep_ms' => 100,

                /*
                 | The class used to serialize the messages published through this connection, resolved from the
                 | service container. When null, the MessageSerializer bound in the container is used, which is
                 | the JSON serializer by default.
                 */
                'serializer' => null,

                /*
                 | librdkafka options applied only to the producer.
                 */
                'options' => [
                    'compression.codec' => env('KAFKA_COMPRESSION_TYPE', 'snappy'),
                ],
            ],

            'consumer' => [
                /*
                 | Consumers in the same group share the topic partitions between them, so each partition is
                 | consumed by a single consumer of the group. The default is named after your application, so
                 | applications consuming from the same cluster don't join each other's group by accident.
                 */
                'group_id' => env('KAFKA_CONSUMER_GROUP_ID', Illuminate\Support\Str::slug(env('APP_NAME', 'laravel'))),

                /*
                 | Whether the consumer commits offsets automatically after handling each message.
                 */
                'auto_commit' => env('KAFKA_AUTO_COMMIT', true),

                /*
                 | How long the consumer waits for a message before polling again.
                 */
                'timeout_ms' => env('KAFKA_CONSUMER_DEFAULT_TIMEOUT', 2000),

                /*
                 | The class used to deserialize the messages consumed through this connection, resolved from the
                 | service container. When null, the MessageDeserializer bound in the container is used, which is
                 | the JSON deserializer by default.
                 */
                'deserializer' => null,

                /*
                 | librdkafka options applied only to consumers. "auto.offset.reset" defines where a consumer
                 | group starts reading when it has no committed offset: "latest", "earliest" or "none".
                 */
                'options' => [
                    'auto.offset.reset' => env('KAFKA_OFFSET_RESET', 'latest'),
                ],
            ],
        ],
    ],

    /*
     | The cache store used to signal consumers to restart when running "php artisan kafka:restart-consumers".
     */
    'cache_driver' => env('KAFKA_CACHE_DRIVER', env('CACHE_DRIVER', env('CACHE_STORE', 'database'))),

    /*
     | The header used to store the message id.
     */
    'message_id_key' => env('MESSAGE_ID_KEY', 'laravel-kafka::message-id'),
];
```
