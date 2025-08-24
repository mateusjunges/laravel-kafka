---
title: Upgrade guide
weight: 6
---

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
