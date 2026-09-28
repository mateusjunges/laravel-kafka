---
title: Upgrade guide
weight: 6
---

## Upgrade to v3.0 from v2.11

### Requirements

The minimum PHP version is now 8.3.

### Connections

The configuration file now defines named connections, each one pointing to a Kafka cluster. Publish the new configuration file and move your settings into the `default` connection:

```bash
php artisan vendor:publish --tag=laravel-kafka-config --force
```

| v2.11 | v3.0 |
| --- | --- |
| `brokers` | `connections.default.brokers` |
| `securityProtocol` | `connections.default.security_protocol` |
| `sasl` | `connections.default.sasl` |
| `consumer_group_id` | `connections.default.consumer.group_id` |
| `consumer_timeout_ms` | `connections.default.consumer.timeout_ms` |
| `offset_reset` | `connections.default.consumer.options.auto.offset.reset` |
| `auto_commit` | `connections.default.consumer.auto_commit` |
| `compression` | `connections.default.producer.options.compression.codec` |
| `flush_timeout_in_ms` | `connections.default.producer.flush_timeout_ms` |
| `flush_retries` | `connections.default.producer.flush_retries` |
| `flush_retry_sleep_in_ms` | `connections.default.producer.flush_retry_sleep_ms` |
| `debug` | `connections.default.options.debug`, set to `all` to enable it |
| `partition`, `sleep_on_error` | Removed, they were not used. |

The environment variables did not change. The SASL configuration of the connection is now used by producers and by consumers created with `Kafka::consumer()`, not only by the `kafka:consume` command. The `security_protocol` is also used when it is not a SASL protocol, so you no longer need to set the `security.protocol` option to use `SSL`.

See the [connections](/advanced-usage/connections) documentation for details.

### Publishing messages

`Kafka::publish()` and `Kafka::publishSync()` now accept the topic instead of the broker, and return a `Junges\Kafka\Producers\PendingMessage`. The brokers come from the connection, use `Kafka::connection('name')->publish()` to publish to another cluster.

```php
// v2.11
Kafka::publish('broker')->onTopic('orders')->withKafkaKey('key')->withBody($body)->send();

// v3.0
Kafka::publish('orders')->withKey('key')->withBody($body)->send();
```

`publish()` is now asynchronous. Every connection has a single producer, shared by every message published through it, and queued messages are flushed when the application terminates, after each queued job and when calling `Kafka::flush()`. Flush failures at these moments are reported to your exception handler instead of thrown. Use `publishSync()` to flush each message as soon as it is sent.

The following methods were removed:

| Removed | Replacement |
| --- | --- |
| `Kafka::asyncPublish()`, `Kafka::publishAsync()` | `Kafka::publish()` |
| `Kafka::fresh()` | Not needed anymore. |
| `withKafkaKey()` | `withKey()` |
| `withConfigOption()`, `withConfigOptions()`, `withTransactionalId()` | The `producer.options` key of the connection. |
| `withDebugEnabled()`, `withDebugDisabled()` | The `debug` option of the connection. |
| `withSasl()` on the producer | The `sasl` key of the connection. |
| `withFlushRetries()`, `withFlushTimeout()` | The `producer.flush_retries` and `producer.flush_timeout_ms` keys of the connection. |
| `withFlushCallback()` on the producer builder | `Kafka::connection()->producer()->withFlushCallback()` |
| `withErrorCb()`, `withLogCb()` and the other configuration callbacks on the producer builder | The same methods on the connection: `Kafka::connection()->withErrorCb()`. |
| `transactional()` | It had no effect. Use the producer of a connection with a `transactional.id`. |
| `build()` on the producer builder | `Kafka::connection()->producer()` |

`send()` now returns `void`. It used to return `true` even when the message was only queued.

The `Junges\Kafka\Producers\Builder` class and the `Junges\Kafka\Contracts\MessageProducer` contract were removed. The `Junges\Kafka\Contracts\Producer` contract methods changed: `produce()` returns `void` and accepts an optional serializer, `flush()` returns `void`, and `withFlushCallback()` was added. The `Junges\Kafka\Contracts\ProducerMessage` contract now requires a `withBodyKey()` method.

### Consuming messages

`Kafka::consumer()` no longer accepts the brokers as its third argument. The consumer uses the brokers, SASL configuration, options and group id of the connection, use `Kafka::connection('name')->consumer()` to consume from another cluster, or `withBrokers()` to override the brokers of a single consumer.

`Junges\Kafka\Consumers\Builder::create()` now receives a `Junges\Kafka\Config\ConnectionConfig` instead of the brokers. You can get one from `Kafka::connection()->getConfig()`.

### Committers

The `Junges\Kafka\Commit\SeekToCurrentErrorCommitter` class, deprecated in v2.12.0, was removed. It did not make failed messages be consumed again. Use `retryFailedMessages()` or a dead letter queue instead, see [handling failed messages](/consuming-messages/handling-failed-messages).

### Manager

The `Junges\Kafka\Factory` is now a singleton, also bound to the `Junges\Kafka\Contracts\Manager` contract. The contract no longer contains the `fresh()`, `shouldFake()` and `shouldReceiveMessages()` methods, and has new `connection()`, `flush()` and `getDefaultConnection()` methods.

### Testing

`Kafka::fake()` now replaces the manager with a `Junges\Kafka\Support\Testing\Fakes\KafkaFake`, which extends the `Factory`, so every connection publishes to the fake. The `ProducerBuilderFake` class was removed, and `KafkaFake` no longer receives the manager in its constructor.

## Upgrade to v2.11 from v2.10

- **BREAKING CHANGE**: Dropped support for Laravel 10 and Laravel 11. The minimum supported Laravel version is now 12.0

## Upgrade to v2.10 from v2.9

No breaking changes. Notable additions:

- **ContextAware exceptions**: Exceptions implementing `Junges\Kafka\Contracts\ContextAware` will now have their context forwarded as headers when messages are sent to the DLQ. See the [configuring consumer options](/consuming-messages/configuring-consumer-options) docs for details.
- **Async producer flush callback**: You can now pass a callback via `withFlushCallback()` on the producer builder to be notified when async messages are flushed.
- **Removed `@internal` annotations** from public interfaces and traits, making them safe to implement/use in userland code.

## Upgrade to v2.9 from v2.8

- **BREAKING CHANGE**: Deprecated producer batch messages feature has been removed (`MessageBatch`, `sendBatch`, `produceBatch`). Use `Kafka::asyncPublish()` instead for better performance
- **BREAKING CHANGE**: Deprecated consumer batch messages feature has been removed (`enableBatching()`, `withBatchSizeLimit()`, `withBatchReleaseInterval()`). Process messages individually in your consumer handler
- Removed classes: `BatchMessageConsumer`, `HandlesBatchConfiguration`, `BatchConfig`, `NullBatchConfig`, `CallableBatchConsumer`, etc.
- Removed events: `BatchMessagePublished`, `MessageBatchPublished`, `PublishingMessageBatch`

## Upgrade to v2.8 from v2.x
The only breaking change in this version was the change in the `Junges\Kafka\Contracts\Handler` contract signature.

The `handle` method now requires a second parameter of type `Junges\Kafka\Contracts\MessageConsumer`.

Here's the updated signature:
```diff
class MyHandler implements Handler {
-    public function __invoke(ConsumerMessage $message): void {
+    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void {
        // Process message here...
    }
}
```

If you are handling your messages using a closure, no changes are needed as the closure signature already supports the second parameter.

## Upgrade to v2.x from v1.13.x

## High impact changes
 - The `\Junges\Kafka\Contracts\CanProduceMessages` contract was renamed to `\Junges\Kafka\Contracts\MessageProducer`
- The `\Junges\Kafka\Contracts\KafkaProducerMessage` contract was renamed to `\Junges\Kafka\Contracts\ProducerMessage`
- The `\Junges\Kafka\Contracts\CanConsumeMessages` was renamed to `\Junges\Kafka\Contracts\MessageConsumer`
- The `\Junges\Kafka\Contracts\KafkaConsumerMessage` was renamed to `\Junges\Kafka\Contracts\ConsumerMessage`
- The `\Junges\Kafka\Contracts\CanPublishMessagesToKafka` contract was removed.
- The `\Junges\Kafka\Contracts\CanConsumeMessagesFromKafka` was removed.
- The `\Junges\Kafka\Contracts\CanConsumeBatchMessages` contract was renamed to `\Junges\Kafka\Contracts\BatchMessageConsumer`
- The `\Junges\Kafka\Contracts\CanConsumeMessages` contract was renamed to `\Junges\Kafka\Contracts\MessageConsumer`
- Introduced a new `\Junges\Kafka\Contracts\Manager` used by `\Junges\Kafka\Factory` class

### The `withSasl` method signature was changed.

The `withSasl` method now accepts all `SASL` parameters instead of a `Sasl` object.
```php
public function withSasl(string $username, string $password, string $mechanisms, string $securityProtocol = 'SASL_PLAINTEXT');
```

### Handler functions require a second parameter

In v2 handler functions and handler classes require a `\Junges\Kafka\Contracts\MessageConsumer` as a second argument.

```diff
$consumer = Kafka::consumer(['topic'])
    ->withConsumerGroupId('group')
-    ->withHandler(function(ConsumerMessage $message) {
+    ->withHandler(function(ConsumerMessage $message, MessageConsumer $consumer) {
        //
    })
```

### Renamed `createConsumer` method
The `Kafka::createConsumer` method has been renamed to just `consumer`

### Renamed `publishOn` method
The `Kafka::publishOn` method has been renamed to `publish`, and it does not accept the `$topics` parameter anymore.

Please chain a call to `onTopic` to specify in which topic the message should be published.

```php
\Junges\Kafka\Facades\Kafka::publish('broker')->onTopic('topic-name');
```

### Setting `onStopConsuming` callbacks

To set `onStopConsuming` callbacks you need to define them while building the consumer, instead of after calling the `build` method as in `v1.13.x`:

```diff
$consumer = Kafka::consumer(['topic'])
    ->withConsumerGroupId('group')
    ->withHandler(new Handler)
+    ->onStopConsuming(static function () {
+        // Do something when the consumer stop consuming messages
+    })
    ->build()
-    ->onStopConsuming(static function () {
-        // Do something when the consumer stop consuming messages
-    })
```


### Updating dependencies
**PHP 8.2 Required**

This package now requires PHP 8.2 or higher.

You can use tools such as [rector](https://github.com/rectorphp/rector) to upgrade your app to PHP 8.2.
