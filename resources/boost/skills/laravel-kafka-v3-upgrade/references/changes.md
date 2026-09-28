# laravel-kafka v2 to v3 changes

Each section lists what to search for, and how to change it. The full upgrade guide is in `vendor/mateusjunges/laravel-kafka/docs/upgrade-guide.md`, and at https://laravelkafka.com.

## Configuration (`config/kafka.php`)

The file now defines named connections. Move each value the application customized into the `default` connection. The v2 defaults are between parentheses, and the environment variables read by each key did not change:

| v2 key (v2 default) | v3 key |
| --- | --- |
| `brokers` (`localhost:9092`) | `connections.default.brokers` |
| `securityProtocol` (`PLAINTEXT`) | `connections.default.security_protocol` |
| `sasl.mechanisms` (`PLAINTEXT`), `sasl.username` (`null`), `sasl.password` (`null`) | `connections.default.sasl.mechanism`, `.username`, `.password`. The default mechanism is `PLAIN` instead of `PLAINTEXT`, which is not a SASL mechanism. |
| `consumer_group_id` (`group`) | `connections.default.consumer.group_id` |
| `consumer_timeout_ms` (`2000`) | `connections.default.consumer.timeout_ms` |
| `offset_reset` (`latest`) | `connections.default.consumer.options` with the `auto.offset.reset` option |
| `auto_commit` (`true`) | `connections.default.consumer.auto_commit`. When `false`, consumers commit manually, like `withManualCommit()`: the handler must call `$consumer->commit($message)`. |
| `compression` (`snappy`) | `connections.default.producer.options` with the `compression.codec` option |
| `flush_timeout_in_ms` (`1000`), `flush_retries` (`10`), `flush_retry_sleep_in_ms` (`100`) | `connections.default.producer.flush_timeout_ms`, `.flush_retries`, `.flush_retry_sleep_ms` |
| `debug` (`false`) | `connections.default.options` with the `debug` option set to `all`. To keep `KAFKA_DEBUG` working: `'options' => env('KAFKA_DEBUG', false) ? ['debug' => 'all'] : []`. |
| `partition`, `sleep_on_error` | Removed, they were never used. Remove `KAFKA_PARTITION` and `KAFKA_ERROR_SLEEP` from the `.env` and deployment files. |
| `cache_driver`, `message_id_key` | Unchanged, at the top level of the file. |

Application code reading the v2 keys, such as `config('kafka.brokers')`, must read the new ones, such as `config('kafka.connections.default.brokers')`.

librdkafka option names contain dots, so write them as array keys of the `options` arrays. Don't set them with the dot notation of `config()`, which would nest them.

Behavior changes:

- The default consumer group is the slug of `APP_NAME` instead of `group`. To keep the previous group, use `env('KAFKA_CONSUMER_GROUP_ID', 'group')` in the published file, and add `KAFKA_CONSUMER_GROUP_ID=group` to `.env.example`.
- When a SASL username is set, SASL is always used: `PLAINTEXT` or no protocol becomes `SASL_PLAINTEXT`, and `SSL` becomes `SASL_SSL`. In v2, the credentials were ignored unless the protocol was already a SASL one.
- The producer and every consumer use the connection SASL configuration. In v2, only the `kafka:consume` command did.
- Connections can set `producer.serializer` and `consumer.deserializer` class names.

## Publishing

Search for: `Kafka::publish(`, `Kafka::asyncPublish(`, `Kafka::publishAsync(`, `Kafka::fresh(`, `withKafkaKey(`, `withConfigOption`, `withTransactionalId(`, `withDebugEnabled(`, `withDebugDisabled(`, `withFlushRetries(`, `withFlushTimeout(`, `withFlushCallback(`, `transactional(`, `->build()` on producers, `Junges\Kafka\Producers\Builder`, `MessageProducer`.

| v2 | v3 |
| --- | --- |
| `Kafka::publish('broker')->onTopic('orders')` | `Kafka::publish('orders')`. The argument is the topic now, and the brokers come from the connection. |
| `Kafka::publish()` (flushed each message) | `Kafka::publishSync('orders')` to keep flushing each message, or `Kafka::publish('orders')` to queue it. Both take the topic. Ask the user. |
| `Kafka::asyncPublish()`, `Kafka::publishAsync()` | `Kafka::publish()` |
| Publishing to another cluster, `Kafka::publish('other-broker:9092')` | Define a connection and use `Kafka::connection('name')->publish('topic')`. |
| `Kafka::fresh()->publish(...)` | `Kafka::publish(...)`. Every connection shares one producer. |
| `->withKafkaKey($key)` | `->withKey($key)` |
| `->withConfigOption()`, `->withConfigOptions()`, `->withTransactionalId()` | The `producer.options` key of the connection. They then apply to every message published through it, so use a dedicated connection, with the same brokers, when only some messages need them. |
| `->withDebugEnabled()`, `->withDebugDisabled()` | The `debug` option of the connection. |
| `->withSasl(...)` on the producer | The `sasl` key of the connection. |
| `->withFlushRetries()`, `->withFlushTimeout()` | The `producer.flush_retries` and `producer.flush_timeout_ms` keys of the connection. |
| `->withFlushCallback(fn (array $messages) => ...)` | Listen to the `Junges\Kafka\Events\MessageDeliveryFailed` event, or register `Kafka::connection()->onDeliveryReport(...)`. |
| `->withErrorCb()`, `->withLogCb()` and the other callbacks on the producer | `Kafka::connection()->onError()`, `->onLog()`, see the callbacks section. Register them in a service provider, before the first publish. |
| `->transactional()` | Set a `transactional.id` in the producer options of a dedicated connection, and use `Kafka::connection('name')->transaction(fn ($connection) => ...)`. |
| `->build()` on the producer builder | `Kafka::connection()->producer()` |
| `$sent = ...->send(); if ($sent) { ... }` | `send()` returns `void`. Use `publishSync()` and remove the uses of the return value. In v2 and v3 alike, `send()` throws `Junges\Kafka\Exceptions\CouldNotPublishMessage` when the flush fails, so the `false` branch was never reached, and no `try`/`catch` is needed to keep the behavior. |

Behavior changes:

- `publish()` queues messages. They are flushed when the application terminates, after each queued job, before a consumer stores or commits an offset, and on `Kafka::flush()`. Failures at those moments are reported to the exception handler.
- The key, headers, body and topic set on a pending message are applied on top of the message given to `withMessage()`, in any order.
- A `Junges\Kafka\Message\Message` gets its id once, when it is created, and `toArray()` includes it.

## Consumers

Search for: `Kafka::consumer(`, `withConsumerGroupId(`, `withMaxMessages(`, `withMaxTime(`, `stopAfterLastMessage(`, `withSecurityProtocol(`, `withSasl(`, `withCommitBatchSize(`, `withMaxCommitRetries(`, `stopOnFailure(`, `withPartitionAssignmentCallback(`, `assignPartitionsWithOffsets(`, `Builder::create(`.

| v2 | v3 |
| --- | --- |
| `Kafka::consumer($topics, $group, $brokers)` | `Kafka::consumer($topics, $group)`. Use `Kafka::connection('name')->consumer(...)` for another cluster. `withBrokers()` also works, but the consumer keeps the SASL and security protocol of its connection, so prefer a connection when the cluster needs other credentials. |
| `->withConsumerGroupId('orders')` | `->withGroupId('orders')` |
| `->withMaxMessages(100)` | `->stopAfterMessages(100)` |
| `->withMaxTime(3600)` | `->stopAfterSeconds(3600)` |
| `->stopAfterLastMessage()` | `->stopWhenEmpty()` |
| `->withSecurityProtocol('SSL')` | The `security_protocol` of the connection, or `->withOption('security.protocol', 'SSL')`. |
| `->withSasl(username: ..., password: ..., mechanisms: ...)` | `->withSasl(username: ..., password: ..., mechanism: ...)`. It also accepts the `Junges\Kafka\Config\SaslMechanism` and `SecurityProtocol` enums. |
| `->withCommitBatchSize()`, `->withMaxCommitRetries()` | Remove them. Offsets are committed in the background, tune it with the `auto.commit.interval.ms` option. |
| `->stopOnFailure()` | Remove it, it is the default. |
| No dead letter queue, relying on failed messages being skipped | `->skipFailedMessages()`, if the user wants to keep the v2 behavior. |
| `->withPartitionAssignmentCallback(fn ($partitions) => ...)` | `->onPartitionsAssigned(fn ($partitions) => ...)` |
| `->assignPartitionsWithOffsets(fn ($partitions) => ...)` | `->resolveOffsetsUsing(fn ($partitions) => ...)` |
| `Builder::create('broker', $topics, $group)` | `Kafka::consumer($topics, $group)`, or `Builder::create(Kafka::connection()->getConfig(), $topics, $group)`. |

Behavior changes:

- Without a dead letter queue, a failed message stops the consumer, which throws `Junges\Kafka\Exceptions\ConsumerException`, and the message is consumed again after a restart.
- Offsets are stored after processing and committed in the background every `auto.commit.interval.ms`, and when the consumer stops.
- The consumer is closed whenever `consume()` returns, so `getAssignedPartitions()` returns an empty array afterwards.
- A message that can't be sent to the dead letter queue stops the consumer without committing its offset.

Unchanged: `subscribe()`, `withBrokers()`, `withHandler()`, `usingDeserializer()`, `usingCommitterFactory()`, `withDlq()`, `withMiddleware()`, `withAutoCommit()`, `withManualCommit()`, `withRebalanceStrategy()`, `withOption()`, `withOptions()`, `assignPartitions()`, `beforeConsuming()`, `afterConsuming()`, `onStopConsuming()` and `build()`. The configuration callbacks stay on the consumer builder under their new names, see the callbacks section.

New features the user may want: `retryFailedMessages($times, backoffInMs: 1000)` or an array of backoffs, `onMessageFailed(fn ($message, $exception) => ...)`, `getAttempts()` on messages, and `Kafka::consumerMiddleware([...])` for global middlewares.

## Handlers and contracts

Search for: `MessageConsumer`, `extends Consumer`, `Contracts\Consumer`, `CallableConsumer`, `producerKey(`, `ConsumerBuilder`, `InteractsWithConfigCallbacks`, `getConsumer()`.

| v2 | v3 |
| --- | --- |
| `use Junges\Kafka\Contracts\MessageConsumer;` and `MessageConsumer $consumer` | `use Junges\Kafka\Contracts\Consumer;` and `Consumer $consumer` |
| `class OrderHandler extends \Junges\Kafka\Contracts\Consumer` with `handle(ConsumerMessage $message, MessageConsumer $consumer)` | `class OrderHandler implements \Junges\Kafka\Contracts\Handler` with `__invoke(ConsumerMessage $message, Consumer $consumer): void`, or a consumer class extending `Junges\Kafka\KafkaConsumer`. When the code moves into a consumer class, delete the old class once nothing references it. |
| Overriding `failed(string $message, string $topic, Throwable $exception)` | `failed(ConsumerMessage $message, Throwable $exception): void` on a consumer class, or `->onMessageFailed(...)` on the builder. It is a notification and can't prevent the failure handling. |
| Overriding `producerKey()` | Remove it. Dead letter queue messages keep their key. |
| `Junges\Kafka\Consumers\CallableConsumer` | `Junges\Kafka\Consumers\MessageHandler` |
| `new Config(consumer: ...)`, `$config->getConsumer()` | `new Config(handler: ...)`, `$config->getHandler()` |
| Type hints on `Junges\Kafka\Contracts\ConsumerBuilder` | `Junges\Kafka\Consumers\Builder` |

## The kafka:consume command

Search for `kafka:consume` in code, Supervisor configurations and deployment files.

The `--topics`, `--consumer`, `--deserializer`, `--groupId`, `--commit`, `--dlq`, `--maxMessage`, `--maxTime` and `--securityProtocol` options were removed. Create a consumer class with `php artisan make:kafka-consumer OrdersConsumer`, which is created in `app/Kafka/Consumers`, and move the configuration into its methods:

```php
namespace App\Kafka\Consumers;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\KafkaConsumer;

class OrdersConsumer extends KafkaConsumer
{
    public function topics(): array
    {
        return ['orders'];
    }

    public function group(): ?string
    {
        return 'orders';
    }

    public function dlq(): string|true|null
    {
        return 'orders-dlq';
    }

    public function handle(ConsumerMessage $message, Consumer $consumer): void
    {
        // The code of the v2 handler class
    }
}
```

Then replace `php artisan kafka:consume --topics=orders --consumer="App\Handlers\OrderHandler" --groupId=orders --dlq=orders-dlq` with `php artisan kafka:consume OrdersConsumer`. The argument is the class name in the `App\Kafka\Consumers` namespace, or a fully qualified class name, such as `"Domain\Orders\OrdersConsumer"`, for classes elsewhere. The `--max-messages`, `--max-time` and `--stop-when-empty` options are available. Other methods of consumer classes: `connection()`, `retries()`, `backoff()`, `skipFailedMessages()`, `middleware()`, `failed()`, and `configure(Builder $builder)` for anything else. Consumer classes are resolved from the container, so their constructor can receive dependencies.

## Configuration callbacks

The callbacks are renamed on the consumer builder, with the same signatures, and move to the connection for producers.

Search for: `withErrorCb(`, `withLogCb(`, `withStatsCb(`, `withRebalanceCb(`, `withOffsetCommitCb(`, `withOAuthBearerTokenRefreshCallback(`, `withDrMsgCb(`, `withConsumeCb(`.

| v2 | v3 |
| --- | --- |
| `withErrorCb()` | `onError()` |
| `withLogCb()` | `onLog()` |
| `withStatsCb()` | `onStatistics()` |
| `withRebalanceCb()` | `onRebalance()`. It can't be combined with `onPartitionsAssigned()` or `resolveOffsetsUsing()`. |
| `withOffsetCommitCb()` | `onOffsetCommit()` |
| `withOAuthBearerTokenRefreshCallback()` | `onOAuthBearerTokenRefresh()` |
| `withDrMsgCb()` | `Kafka::connection()->onDeliveryReport()` |
| `withConsumeCb()` | Remove it, it was never called. |

## Middlewares

Search for: `implements Middleware`, `withMiddleware(`.

- Add the `mixed` return type: `public function __invoke(ConsumerMessage $message, callable $next): mixed`.
- Middleware classes given by name are resolved from the container once per consumer, so the same instance handles every message. Move state about a single message out of their properties.

## Committers, messages and serializers

Search for: `implements Committer`, `CommitterFactory`, `commitMessage(`, `commitDlq(`, `BatchCommitter`, `RetryableCommitter`, `SeekToCurrentErrorCommitter`, `implements ConsumerMessage`, `implements ProducerMessage`, `implements KafkaMessage`, `implements MessageDeserializer`, `getKafkaErrorCode(`, `setTopicName(`, `RetryableHandler`, `RetryStrategy`, `Retryable`, `Sleeper`.

| v2 | v3 |
| --- | --- |
| `commitMessage()` and `commitDlq()` on custom committers | Remove them. Committers only handle `commit()` and `commitAsync()`, typed `ConsumerMessage\|Message\|array\|null $messageOrOffsets = null`. |
| `BatchCommitter`, `RetryableCommitter`, `SeekToCurrentErrorCommitter` | Removed. Use `retryFailedMessages()` or a dead letter queue for failures. |
| Custom `KafkaMessage` implementations | `getHeaders(): array` and `getBody(): mixed`. |
| Custom `ConsumerMessage` implementations | Add `getAttempts(): int` and `withAttempts(int $attempts): static`. |
| Custom `ProducerMessage` implementations | Add `withBodyKey(string $key, mixed $value): self`. |
| `CouldNotPublishMessage::getKafkaErrorCode()` | `getCode()` |
| `$message->setTopicName($topic)` | `$message->onTopic($topic)` |
| `Junges\Kafka\Handlers\RetryableHandler` and retry strategies | `retryFailedMessages()` on the builder, or `retries()` and `backoff()` on consumer classes. |

## Tests

Search for: `Kafka::fake(`, `shouldReceiveMessages(`, `assertPublished`, `new ConsumedMessage(`, `ProducerBuilderFake`, `new KafkaFake(`.

- The fake records published messages when `send()` is called, with both `publish()` and `publishSync()`, so the publishing assertions keep working without changes.
- Optionally, `Kafka::assertPublishedOn('topic', null, fn ($message) => ...)` can be written `Kafka::assertPublishedOn('topic', fn ($message) => ...)`. The `$expectedMessage` parameter was renamed to `$expected`. When a message and a callback are both given, a published message must match both.
- New assertions: `assertNotPublished()` and `assertNothingPublishedOn($topic)`.
- `new ConsumedMessage(topicName: 'orders', body: [...])` is enough, the other arguments have defaults.
- Faked consumers handle failures like real ones: a failing handler without a dead letter queue makes `consume()` throw `ConsumerException`, with the handler exception in `getPrevious()`. Update tests that expected the handler exception itself, or add `skipFailedMessages()` to the consumer.
- `ProducerBuilderFake` was removed and `KafkaFake` has no constructor arguments.
- Consumer classes are tested with `Kafka::consumerFor(OrdersConsumer::class)->build()->consume()` after `Kafka::fake()` and `Kafka::shouldReceiveMessages([...])`.
