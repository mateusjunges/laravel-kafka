---
title: Upgrade guide
weight: 6
---

## Upgrade to v3.0 from v2.11

Version 3.0 has many breaking changes. The ones most likely to affect your application are:

- **Connections**: the configuration file now defines named connections. Publish the new configuration file and move your settings into it, see [connections](#connections).
- **Publishing**: `Kafka::publish()` receives the topic instead of the broker, is asynchronous, `withKafkaKey()` was renamed to `withKey()`, and `withBodyKey()` was removed in favor of `withBody()`. See [publishing messages](#publishing-messages).
- **Failed messages**: without a dead letter queue, a failed message now stops the consumer instead of being skipped. Call `skipFailedMessages()` to keep the v2 behavior, see [failed messages](#failed-messages).
- **The `kafka:consume` command** now runs consumer classes, see [the kafka:consume command](#the-kafkaconsume-command).
- **Handlers**: the `Junges\Kafka\Contracts\MessageConsumer` argument of handlers was renamed to `Junges\Kafka\Contracts\Consumer`, see [consumer contracts and handlers](#consumer-contracts-and-handlers).

Changes that affect fewer applications:

- Several consumer builder methods were renamed, see [stopping consumers](#stopping-consumers), [configuration callbacks](#configuration-callbacks) and [consuming messages](#consuming-messages).
- Auto commit no longer commits every message synchronously, and custom committers only handle manual commits, see [committers](#committers).
- Middleware classes must declare a return type, see [middlewares](#middlewares).
- Faked consumers handle failed messages like real ones, see [testing](#testing).

### Upgrading with an AI agent

The package ships a `laravel-kafka-v3-upgrade` skill, which guides AI agents through this upgrade. It finds the code affected by each change, asks you about the changes that alter runtime behavior, and migrates the configuration, the code and the deployment files. After requiring version 3.0, install it with [Laravel Boost](https://github.com/laravel/boost) by running `php artisan boost:install`, or `php artisan boost:update --discover` if Boost is already installed. Without Boost, copy the `vendor/mateusjunges/laravel-kafka/resources/boost/skills/laravel-kafka-v3-upgrade` directory into the skills directory of your agent, for example `.claude/skills`. Then ask your agent to upgrade laravel-kafka to v3.

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
| `sasl` | `connections.default.sasl`, where the `mechanisms` key was renamed to `mechanism`. The old key is still read. |
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

The default consumer group is now named after your application, the slug of `APP_NAME`, instead of `group`, so applications consuming from the same cluster don't share a group by accident. **If your consumers relied on the default group, set `KAFKA_CONSUMER_GROUP_ID=group` before upgrading**: a consumer in a new group has no committed offsets, so depending on `auto.offset.reset`, it would consume the topics again from the beginning, or skip the messages published while it was being deployed.

The environment variables did not change. The SASL configuration of the connection is now used by producers and by consumers created with `Kafka::consumer()`, not only by the `kafka:consume` command. The `security_protocol` is also used when it is not a SASL protocol, so you no longer need to set the `security.protocol` option to use `SSL`.

SASL credentials are now used whenever a username is set. With `security_protocol` set to `PLAINTEXT` or not set, `SASL_PLAINTEXT` is used, and with `SSL`, `SASL_SSL` is used. Previously, the credentials were silently ignored unless the security protocol was already a SASL one. The `securityProtocol` argument and the `getSecurityProtocol()` method of `Junges\Kafka\Config\Sasl` were removed, as the security protocol is part of the connection and consumer configuration.

See the [connections](/advanced-usage/connections) documentation for details.

### Publishing messages

`Kafka::publish()` and `Kafka::publishSync()` now accept the topic instead of the broker, and return a `Junges\Kafka\Producers\PendingMessage`. The brokers come from the connection, use `Kafka::connection('name')->publish()` to publish to another cluster.

```php
// v2.11
Kafka::publish('broker')->onTopic('orders')->withKafkaKey('key')->withBody($body)->send();

// v3.0
Kafka::publish('orders')->withKey('key')->withBody($body)->send();
```

`publish()` is now asynchronous. Every connection has a single producer, shared by every message published through it, and queued messages are flushed when the application terminates, after each queued job, before a consumer stores or commits the offset of a message, and when calling `Kafka::flush()`. Flush failures at these moments are reported to your exception handler instead of thrown. Use `publishSync()` to flush each message as soon as it is sent.

The following methods were removed:

| Removed | Replacement |
| --- | --- |
| `Kafka::asyncPublish()`, `Kafka::publishAsync()` | `Kafka::publish()` |
| `Kafka::fresh()` | Not needed anymore. |
| `withKafkaKey()` | `withKey()` |
| `withBodyKey()` on the producer builder and on `Junges\Kafka\Message\Message`, and `forgetBodyKey()` on `Message` | `withBody()`, with every key of the body: `->withBodyKey('id', 1)->withBodyKey('status', 'paid')` becomes `->withBody(['id' => 1, 'status' => 'paid'])`. |
| `withConfigOption()`, `withConfigOptions()`, `withTransactionalId()` | The `producer.options` key of the connection. |
| `withDebugEnabled()`, `withDebugDisabled()` | The `debug` option of the connection. |
| `withSasl()` on the producer | The `sasl` key of the connection. |
| `withFlushRetries()`, `withFlushTimeout()` | The `producer.flush_retries` and `producer.flush_timeout_ms` keys of the connection. |
| `withFlushCallback()` on the producer builder | Removed, as it reported every flushed message, including the ones that failed. Listen to the `MessageDeliveryFailed` event, or register a delivery report callback with `Kafka::connection()->onDeliveryReport()`. |
| `withErrorCb()`, `withLogCb()` and the other configuration callbacks on the producer builder | The renamed methods on the connection, such as `Kafka::connection()->onError()`. See the configuration callbacks section below. |
| `transactional()` | It had no effect. Use the producer of a connection with a `transactional.id`. |
| `build()` on the producer builder | `Kafka::connection()->producer()` |

The key, headers, body and topic set on a pending message are now applied on top of the message given to `withMessage()`, whether they are set before or after it. Previously, `withMessage()` discarded the changes made before it. The given message is no longer modified when it is sent.

The id of a `Junges\Kafka\Message\Message` is now generated once, when the message is created, instead of every time its headers were read. `getMessageIdentifier()` returns the same id for the whole life of the message, it matches the id sent to Kafka and the one in the `PublishingMessage` and `MessagePublished` events, and an id set in the message headers is no longer replaced. As the id is now part of the message headers, it is included in `toArray()`. `withHeaders()` keeps the id, unless the given headers contain one. The assertions of `Kafka::fake()` ignore the id when comparing messages.

`send()` now returns `void`. It used to return `true` even when the message was only queued.

The `Junges\Kafka\Producers\Builder` class and the `Junges\Kafka\Contracts\MessageProducer` contract were removed. The `Junges\Kafka\Contracts\Producer` contract methods changed: `produce()` returns `void` and accepts an optional serializer, and `flush()` returns `void`.

### Consuming messages

`Kafka::consumer()` no longer accepts the brokers as its third argument. The consumer uses the brokers, SASL configuration, options and group id of the connection, use `Kafka::connection('name')->consumer()` to consume from another cluster, or `withBrokers()` to override the brokers of a single consumer.

`Junges\Kafka\Consumers\Builder::create()` now receives a `Junges\Kafka\Config\ConnectionConfig` instead of the brokers. You can get one from `Kafka::connection()->getConfig()`.

The order of the consumer builder methods no longer matters, as long as `build()` is called last:

- `withDlq()` without a topic name can be called before `subscribe()`. The dead letter queue is named when the consumer is built, after the first subscribed topic, or the topic of the first assigned partition. The `ConsumerException` thrown when there is no topic is now thrown by `build()`.
- `withPartitionAssignmentCallback()` was renamed to `onPartitionsAssigned()`, and `assignPartitionsWithOffsets()`, which sounded like a static assignment but receives a callback called on every rebalance, was renamed to `resolveOffsetsUsing()`. They no longer replace each other: when both are used, the partitions are assigned with the offsets returned by `resolveOffsetsUsing()`, and then passed to `onPartitionsAssigned()`. Combining them with `onRebalance()` now throws a `LogicException` when the consumer is built, instead of the last one silently replacing the others.

The consumer now retries only fetching messages when Kafka times out, not handling them. Previously, a timeout while handling a message, such as a commit that still timed out after the committer retries, made the consumer fetch the next message, skipping the one being handled. Now the exception is thrown by `consume()` and the consumer stops.

The consumer is now closed whenever `consume()` returns or throws, not only when it stops on a failure. Closing it commits the stored offsets, when auto commit is enabled, and leaves the consumer group right away, so its partitions are reassigned without waiting for the session to time out. As a consequence, `getAssignedPartitions()` returns an empty array once `consume()` returns.

### The kafka:consume command

The `kafka:consume` command now runs consumer classes, which extend `Junges\Kafka\KafkaConsumer`, instead of receiving the topics, the handler and the rest of the configuration as options. Its `--topics`, `--consumer`, `--deserializer`, `--groupId`, `--commit`, `--dlq`, `--maxMessage`, `--maxTime` and `--securityProtocol` options were removed, along with the `Junges\Kafka\Console\Commands\KafkaConsumer\Options` class. Move the configuration to a consumer class, which you can create with `php artisan make:kafka-consumer`:

```bash
# v2.x
php artisan kafka:consume --topics=orders --consumer="App\Handlers\OrderHandler" --groupId=orders --dlq=orders-dlq

# v3.0
php artisan kafka:consume OrdersConsumer
```

The `--max-messages`, `--max-time` and `--stop-when-empty` options are still available. See [consumer classes](/consuming-messages/class-structure) for details.

### Consumer contracts and handlers

The names of the consumer types were cleaned up:

- `Junges\Kafka\Contracts\MessageConsumer`, the consumer passed to handlers, middlewares and callbacks, was renamed to `Junges\Kafka\Contracts\Consumer`. Update the type of the `$consumer` argument of your handlers.
- The abstract `Junges\Kafka\Contracts\Consumer` class, previously used as the base of handler classes, was removed. Handler classes implement the `Junges\Kafka\Contracts\Handler` interface instead, or extend `Junges\Kafka\KafkaConsumer`. Its `failed()` method was replaced by the `failed()` method of consumer classes and the `onMessageFailed()` method of the consumer builder, which are notified of failed messages. Its `producerKey()` method was removed, messages sent to the dead letter queue keep their key.
- The `Junges\Kafka\Contracts\ConsumerBuilder` and `Junges\Kafka\Contracts\InteractsWithConfigCallbacks` contracts were removed, as nothing depended on them. Type hint the `Junges\Kafka\Consumers\Builder` class instead.
- `Junges\Kafka\Consumers\CallableConsumer` was renamed to `Junges\Kafka\Consumers\MessageHandler`, and the `consumer` argument of `Junges\Kafka\Config\Config` was renamed to `handler`, with its `getConsumer()` method renamed to `getHandler()`.

```diff
-use Junges\Kafka\Contracts\MessageConsumer;
+use Junges\Kafka\Contracts\Consumer;

-function (ConsumerMessage $message, MessageConsumer $consumer) {
+function (ConsumerMessage $message, Consumer $consumer) {
```

### Middlewares

The `__invoke` method of the `Junges\Kafka\Contracts\Middleware` interface now declares a `mixed` return type. Add it to your middleware classes:

```diff
-public function __invoke(ConsumerMessage $message, callable $next)
+public function __invoke(ConsumerMessage $message, callable $next): mixed
```

Middleware classes given by name, such as `withMiddleware(LogMessages::class)`, are now resolved from the service container instead of being created with `new`, so their constructor can receive dependencies. They are resolved once per consumer, instead of once per message, so the same instance handles every message. Middlewares can also be registered for every consumer with `Kafka::consumerMiddleware()`, see [middlewares](/advanced-usage/middlewares).

### Stopping consumers

The consumer builder methods that stop the consumer were renamed, to read as a family and to make the units explicit. They match the options of the `kafka:consume` command:

| v2.11 | v3.0 |
| --- | --- |
| `withMaxMessages()` | `stopAfterMessages()` |
| `withMaxTime()` | `stopAfterSeconds()` |
| `stopAfterLastMessage()` | `stopWhenEmpty()` |

### Consumer builder

The `withConsumerGroupId()` method of the consumer builder was renamed to `withGroupId()`, and it no longer accepts `null`. The group of the connection is used when it is not called.

The `$mechanisms` parameter of `withSasl()` was renamed to `$mechanism`, as it receives a single mechanism, which affects calls using named arguments. `withSasl()` now also accepts the new `Junges\Kafka\Config\SaslMechanism` and `Junges\Kafka\Config\SecurityProtocol` enums, and when no security protocol is given, it keeps the encryption of the connection: `SASL_SSL` is used when the connection uses `SSL` or `SASL_SSL`, and `SASL_PLAINTEXT` otherwise. Previously, `SASL_PLAINTEXT` was always used.

The `withSecurityProtocol()` method of the consumer builder was removed, as a consumer uses the security protocol of its connection, or the one given to `withSasl()`. To use another security protocol for a single consumer, set the `security.protocol` option with `withOption()`. The `mechanisms` argument of the `Junges\Kafka\Config\Sasl` constructor and its `getMechanisms()` method were renamed to `mechanism` and `getMechanism()`.

### Configuration callbacks

The methods setting librdkafka configuration callbacks were renamed, on both the consumer builder and connections:

| v2.11 | v3.0 |
| --- | --- |
| `withErrorCb()` | `onError()` |
| `withLogCb()` | `onLog()` |
| `withStatsCb()` | `onStatistics()` |
| `withRebalanceCb()` | `onRebalance()` |
| `withOffsetCommitCb()` | `onOffsetCommit()` |
| `withOAuthBearerTokenRefreshCallback()` | `onOAuthBearerTokenRefresh()` |
| `withDrMsgCb()` | `onDeliveryReport()`, only on connections, as delivery reports are only sent to producers. |
| `withConsumeCb()` | Removed. librdkafka only calls it from its poll based consume API, which the consumer does not use, so it was never called. |

### Failed messages

Failed messages are now safe by default. Without a dead letter queue, the consumer stops when a message fails, after its retries are used, and `consume()` throws a `Junges\Kafka\Exceptions\ConsumerException`. The offset of the failed message is not committed, so it is consumed again once the consumer is restarted. In v2, the consumer committed the offset of the failed message and moved on, losing it.

This is what `stopOnFailure()` did in v2.12, so that method was removed. Remove it from your consumers. To keep the v2 behavior, call the new `skipFailedMessages()` method. A `Junges\Kafka\Events\MessageSkipped` event is dispatched for every skipped message.

```php
// v2.x: skipping failed messages was the default
Kafka::consumer(['page-views'])->withHandler($handler);

// v3.0
Kafka::consumer(['page-views'])->skipFailedMessages()->withHandler($handler);
```

The `kafka:consume` command follows the new default as well.

With auto commit enabled, every consumer now sets the `enable.auto.offset.store` option to `false` and stores the offset of each message only after it is processed or skipped, even when set to `true` through `withOptions()`. In v2, this only happened when `stopOnFailure()` or `retryFailedMessages()` were used.

See [handling failed messages](/consuming-messages/handling-failed-messages) for details.

The `getHeaders()` method of the `Junges\Kafka\Contracts\KafkaMessage` contract now returns `array` instead of `?array`, as headers are never null, and `getBody()` declares a `mixed` return type. Custom implementations of the message contracts must update their signatures.

The `Junges\Kafka\Contracts\ConsumerMessage` contract has new `getAttempts()` and `withAttempts()` methods, which expose how many times the handler was called with a message when failed messages are retried. Custom implementations of the contract must add them. Custom deserializers that return a new message should create it with the attempts of the original message, or leave the default of `1`, as the consumer sets the attempt number after deserializing.

Failed messages are now published to the dead letter queue by the package producer, using the producer options and flush settings of the connection, except `transactional.id`. They are flushed right away, and if the dead letter queue can't be reached, `consume()` throws a `Junges\Kafka\Exceptions\CouldNotPublishMessage` exception without storing the offset of the message, so it is consumed again. Previously, the flush result was ignored, and the offset was stored even when the message never reached the dead letter queue. As they are published by the package producer, dead letter queue messages also dispatch the `PublishingMessage` and `MessagePublished` events.

### Committers

In auto commit mode, the consumer no longer commits the offset of each message synchronously. Offsets are stored after each message is processed, and librdkafka commits them in the background every `auto.commit.interval.ms`, 5 seconds by default, and when the consumer stops. In our measurements against a local broker, this made consuming more than ten times faster. If the consumer process crashes, the messages processed since the last background commit are consumed again, while in v2 at most the last batch was. Lower the `auto.commit.interval.ms` option to make that window shorter. Background commit failures are reported to the offset commit callback, set with `onOffsetCommit()`.

As a consequence, the following were removed:

- The `commitMessage()` and `commitDlq()` methods of the `Junges\Kafka\Contracts\Committer` contract, which now only handles the commits made by handlers through `commit()` and `commitAsync()`. To monitor failed messages, listen to the `Junges\Kafka\Events\MessageSkipped` and `Junges\Kafka\Events\MessageSentToDLQ` events.
- The `withCommitBatchSize()` and `withMaxCommitRetries()` consumer builder methods, and the `--commit` option of the `kafka:consume` command.
- The `Junges\Kafka\Commit\BatchCommitter` and `Junges\Kafka\Commit\RetryableCommitter` classes. `DefaultCommitterFactory` no longer receives a `MessageCounter`.
- The `commit` and `maxCommitRetries` arguments of `Junges\Kafka\Config\Config`, with their `getCommit()` and `getMaxCommitRetries()` methods.

The `commit()` and `commitAsync()` methods of the `Junges\Kafka\Contracts\Consumer` and `Junges\Kafka\Contracts\Committer` contracts now declare the values they accept, `ConsumerMessage|Message|array|null`, instead of `mixed`, and the parameter of `Consumer::commitAsync()` was renamed from `$message_or_offsets` to `$messageOrOffsets`, like the other ones. Custom committers must update their signatures.

The `Junges\Kafka\Commit\SeekToCurrentErrorCommitter` class, deprecated in v2.12.0, was removed. It did not make failed messages be consumed again. Use `retryFailedMessages()` or a dead letter queue instead, see [handling failed messages](/consuming-messages/handling-failed-messages).

### Removed classes and methods

The following classes and methods were removed, as they were no longer used or were superseded:

- `Junges\Kafka\Handlers\RetryableHandler`, `Junges\Kafka\Handlers\RetryStrategies\DefaultRetryStrategy` and the `Junges\Kafka\Contracts\RetryStrategy` contract. Use `retryFailedMessages()` on the consumer builder, or the `retries()` and `backoff()` methods of consumer classes, see [handling failed messages](/consuming-messages/handling-failed-messages).
- `Junges\Kafka\Retryable`, the `Junges\Kafka\Contracts\Sleeper` contract and `Junges\Kafka\Commit\NativeSleeper`.
- `CouldNotPublishMessage::getKafkaErrorCode()` and `CouldNotPublishMessage::flushError()`. The librdkafka error code is available through `getCode()`.
- `AbstractMessage::setTopicName()`. Use `onTopic()` on messages you publish.

### Manager

The `Junges\Kafka\Factory` is now a singleton, also bound to the `Junges\Kafka\Contracts\Manager` contract. The contract no longer contains the `fresh()`, `shouldFake()` and `shouldReceiveMessages()` methods, and has new `connection()`, `flush()` and `getDefaultConnection()` methods.

### Testing

Faked consumers now handle failed messages like real ones: they retry them, call the failure callback, and send them to the dead letter queue, which only dispatches the `MessageSentToDLQ` event, skip them, or stop. A test with a failing handler and no dead letter queue now gets a `Junges\Kafka\Exceptions\ConsumerException`, with the exception thrown by the handler available through `getPrevious()`, instead of that exception itself. Faked consumers also dispatch the `StartedConsumingMessage` and `MessageConsumed` events, and run the `beforeConsuming()` and `afterConsuming()` callbacks.

The assertions of `Kafka::fake()` now accept a callback in place of the expected message, as in `Kafka::assertPublishedOn('orders', fn ($message) => ...)`, and their `$expectedMessage` parameter was renamed to `$expected`, which affects calls using named arguments. When both an expected message and a callback are given, a published message must now match both: previously, the expected message was ignored when a callback was given. The new `assertNotPublished()` and `assertNothingPublishedOn()` assertions were added, and all the constructor arguments of `ConsumedMessage` except the topic and the body now have defaults.

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
