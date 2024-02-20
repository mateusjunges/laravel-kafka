---
title: Upgrade guide
weight: 6
---

## Upgrade to v2.x from v1.13.x

### High impact changes
 - The `\Junges\Kafka\Contracts\CanProduceMessages` contract was renamed to `\Junges\Kafka\Contracts\MessageProducer`
 - The `\Junges\Kafka\Contracts\CanPublishMessagesToKafka` contract was renamed to `\Junges\Kafka\Contracts\MessagePublisher`
- The `\Junges\Kafka\Contracts\KafkaProducerMessage` contract was renamed to `\Junges\Kafka\Contracts\ProducerMessage`
- The `\Junges\Kafka\Contracts\CanConsumeMessages` was renamed to `\Junges\Kafka\Contracts\MessageConsumer`
- The `\Junges\Kafka\Contracts\KafkaConsumerMessage` was renamed to `\Junges\Kafka\Contracts\ConsumerMessage`
- The `\Junges\Kafka\Contracts\CanConsumeMessagesFromKafka` was renamed to `\Junges\Kafka\Contracts\ConsumeMessagesFromKafka`
- The `\Junges\Kafka\Contracts\CanConsumeBatchMessages` contract was renamed to `\Junges\Kafka\Contracts\BatchMessageConsumer`
- The `\Junges\Kafka\Contracts\CanConsumeMessages` contract was renamed to `\Junges\Kafka\Contracts\MessageConsumer`

#### The `withSasl` method signature was changed.

The `withSasl` method now accepts all `SASL` parameters instead of a `Sasl` object.
```php
public function withSasl(string $username, string $password, string $mechanisms, string $securityProtocol = 'SASL_PLAINTEXT');
```

#### Setting `onStopConsuming` callbacks

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
