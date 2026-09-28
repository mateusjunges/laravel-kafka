---
name: laravel-kafka-v3-upgrade
description: Upgrade a Laravel application from mateusjunges/laravel-kafka v2 to v3. Use when upgrading the package, when composer.json requires mateusjunges/laravel-kafka ^2, or when the code uses v2 APIs such as Kafka::publish('broker'), withKafkaKey(), withBodyKey(), asyncPublish(), MessageConsumer handlers, classes extending Junges\Kafka\Contracts\Consumer, withConsumerGroupId(), withMaxMessages(), stopOnFailure(), the flat config/kafka.php keys, or the kafka:consume command with --topics and --consumer options.
---

# Upgrading laravel-kafka from v2 to v3

Version 3 of `mateusjunges/laravel-kafka` changes the configuration file, the publishing API, the consumer defaults and many method names. Upgrade in the order below. Several changes alter runtime behavior without any error, so never guess the answer to the decisions in step 3: ask the user.

The complete list of changes, with the code to search for and its replacement, is in [references/changes.md](references/changes.md). Read it before editing code.

## 1. Check the requirements

- PHP 8.3 or newer, Laravel 12 or newer, and the `rdkafka` extension 6.x.
- The application must be on laravel-kafka 2.x. When it is on an older major version, upgrade to the latest 2.x release first.
- Make sure the working tree is committed, so every change of the upgrade can be reviewed.

## 2. Find everything the upgrade affects

Search the whole application except `vendor/` and `node_modules/`: `app/`, `routes/`, `config/`, `tests/`, service providers, `.env` files, and deployment files such as Supervisor configurations, Procfiles, Dockerfiles, Docker Compose files, Kubernetes manifests and CI scripts, since they may run `php artisan kafka:consume`. Quote the globs, since some shells expand them otherwise.

List every usage of the package:

```bash
grep -rnE "Junges\\\\Kafka|Kafka::|kafka:consume|config\(['\"]kafka\.|KAFKA_" . --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=.git --include='*.php' --include='*.conf' --include='*.ini' --include='*.yml' --include='*.yaml' --include='*.sh' --include='*.json' --include='Procfile' --include='Dockerfile*' --include='.env*'
```

Then list the usages of APIs that no longer exist, which must all be gone at the end of the upgrade:

```bash
grep -rnE "withKafkaKey|withBodyKey|forgetBodyKey|asyncPublish|publishAsync|Kafka::fresh|Kafka::publish\((config|['\"][^'\"]*:[0-9]+)|MessageConsumer|extends +(\\\\?Junges\\\\Kafka\\\\Contracts\\\\)?Consumer\b|CallableConsumer|producerKey\(|withConsumerGroupId|withMaxMessages|withMaxTime|stopAfterLastMessage|stopOnFailure|withCommitBatchSize|withMaxCommitRetries|withSecurityProtocol|with(Error|Log|Stats|Rebalance|OffsetCommit|DrMsg|Consume)Cb|withOAuthBearerTokenRefreshCallback|withPartitionAssignmentCallback|assignPartitionsWithOffsets|withConfigOptions?\(|withDebug(En|Dis)abled|withFlush(Retries|Timeout|Callback)|withTransactionalId|kafka:consume +--|kafka\.(brokers|securityProtocol|sasl|consumer_group_id|consumer_timeout_ms|offset_reset|auto_commit|compression|debug|flush_|partition|sleep_on_error)|KAFKA_(ERROR_SLEEP|PARTITION)" . --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=.git --include='*.php' --include='*.conf' --include='*.ini' --include='*.yml' --include='*.yaml' --include='*.sh' --include='Procfile' --include='Dockerfile*' --include='.env*'
```

Summarize the findings for the user, grouped by area: configuration, publishing, consumers, consumer commands and deployment files, custom committers or messages, and tests.

## 3. Ask the user about the behavior changes

Ask these questions, explain the trade off of each, and wait for the answers before editing code. Skip a question when the search shows it doesn't apply.

1. **Consumer group.** Ask only when some consumer relies on the default group, meaning it passes no group to `Kafka::consumer()` and has no `withConsumerGroupId()` or `--groupId`, and `KAFKA_CONSUMER_GROUP_ID` is not set. The default group changed from `group` to the slug of `APP_NAME`, so those consumers would join a new group without committed offsets and read their topics again from the beginning, or skip messages, depending on `auto.offset.reset`. Recommend keeping the existing group, unless the user wants the new default.
2. **Failed messages.** In v2, a message whose handler throws is skipped and committed when there is no dead letter queue. In v3, the consumer stops without committing it, and the process monitor restarts it, so the message is retried until it succeeds. For each consumer without a dead letter queue, ask whether to keep the v2 behavior, by adding `skipFailedMessages()`, or to adopt the new default. When the user is unsure, keep the v2 behavior, since an upgrade should not change what happens to failed messages silently.
3. **Asynchronous publishing.** In v2, `Kafka::publish()` flushed every message before returning, and `send()` threw `CouldNotPublishMessage` when the flush failed. In v3, `Kafka::publish()` queues the message and delivers it in the background, while `Kafka::publishSync()` keeps the v2 behavior. Ask whether to use `publish()`, which is faster, or `publishSync()` where the code must know the message was delivered before continuing, for example before responding to a request that promises the message was sent, or where it used the result of `send()`. The v2 `asyncPublish()` and `publishAsync()` become `publish()`.
4. **SASL.** Ask only when `KAFKA_USERNAME` or a SASL username is set somewhere while the security protocol is `PLAINTEXT` or `SSL`. In v2 those credentials were ignored, and in v3 they enable SASL, so the application would authenticate against brokers that may not expect it. Ask whether the brokers use SASL. If they don't, remove the username. If they do, make sure the mechanism is a real SASL mechanism, since the v2 default `PLAINTEXT` is not one: `PLAIN`, `SCRAM-SHA-256`, `SCRAM-SHA-512`, `GSSAPI` or `OAUTHBEARER`.
5. **Consumer commands.** The `kafka:consume` command no longer accepts `--topics`, `--consumer` and the other configuration options. It runs consumer classes. Confirm the name and location of the consumer classes to create, by default `app/Kafka/Consumers`. Custom Artisan commands calling `Kafka::consumer()` keep working after the method renames, so they don't need to become consumer classes, unless the user prefers it.

## 4. Update the package

Skip this step when `composer.json` already requires `^3.0`, for example because the package was updated to install this skill.

```bash
composer require mateusjunges/laravel-kafka:^3.0 --with-all-dependencies
```

## 5. Migrate the configuration

1. If `config/kafka.php` exists, write down the values that differ from the v2 defaults listed in the configuration table of the reference, since the next command replaces the file. Then publish the new file with `php artisan vendor:publish --tag=laravel-kafka-config --force` and move the customized values into the `connections.default` connection, following the configuration table of the reference. The environment variables keep their names, except for the ones listed as removed. The new file looks like this:

   ```php
   return [
       'default' => env('KAFKA_CONNECTION', 'default'),

       'connections' => [
           'default' => [
               'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),
               'security_protocol' => env('KAFKA_SECURITY_PROTOCOL', 'PLAINTEXT'),
               'sasl' => ['mechanism' => ..., 'username' => ..., 'password' => ...],
               'options' => [],            // librdkafka options for producers and consumers
               'producer' => [
                   'flush_timeout_ms' => 1000,
                   'flush_retries' => 10,
                   'flush_retry_sleep_ms' => 100,
                   'serializer' => null,
                   'options' => ['compression.codec' => env('KAFKA_COMPRESSION_TYPE', 'snappy')],
               ],
               'consumer' => [
                   'group_id' => env('KAFKA_CONSUMER_GROUP_ID', Str::slug(env('APP_NAME', 'laravel'))),
                   'auto_commit' => env('KAFKA_AUTO_COMMIT', true),
                   'timeout_ms' => env('KAFKA_CONSUMER_DEFAULT_TIMEOUT', 2000),
                   'deserializer' => null,
                   'options' => ['auto.offset.reset' => env('KAFKA_OFFSET_RESET', 'latest')],
               ],
           ],
       ],

       'cache_driver' => ...,     // unchanged, top level
       'message_id_key' => ...,   // unchanged, top level
   ];
   ```

2. When the user keeps the previous consumer group, make `group` the fallback in the published file, `env('KAFKA_CONSUMER_GROUP_ID', 'group')`, so it doesn't depend on every environment setting the variable, and add `KAFKA_CONSUMER_GROUP_ID=group` to `.env.example`.
3. When the application set `KAFKA_DEBUG`, keep it working with `'options' => env('KAFKA_DEBUG', false) ? ['debug' => 'all'] : []` in the connection. Otherwise, remove `KAFKA_DEBUG`, `KAFKA_ERROR_SLEEP` and `KAFKA_PARTITION` from the `.env` files and the deployment files, since v3 doesn't read them.
4. Code reading the v2 keys with `config('kafka.brokers')` or similar must read the new keys, for example `config('kafka.connections.default.brokers')`. Most of the time, that code passed brokers to the package, which is no longer needed.
5. Move producer settings that were set on each publish, such as `withConfigOptions()`, `withSasl()` or `withDebugEnabled()`, to the `producer.options` or `sasl` keys of a connection. Tell the user those options now apply to every message published through the connection. When only some messages need them, define a dedicated connection, with the same brokers, and publish those messages with `Kafka::connection('name')->publish(...)`.
6. Code publishing or consuming to other brokers needs one connection per cluster. Consumers can also call `withBrokers()`, but it only replaces the brokers, and the consumer keeps the SASL and security protocol of its connection, so use a connection whenever the cluster needs other credentials.
7. Configuration callbacks registered on producers, such as `withErrorCb()`, move to the connection, for example `Kafka::connection()->onError()` in the `boot` method of a service provider. They must be registered before the first message is published. On consumers, they are renamed and stay on the builder, with the same callback signatures.

## 6. Migrate the code

Apply the changes of [references/changes.md](references/changes.md), area by area:

- Publishing: `Kafka::publish('broker')->onTopic('orders')` becomes `Kafka::publish('orders')`, or `Kafka::publishSync('orders')`, since both take the topic. `withKafkaKey()` becomes `withKey()`, consecutive `withBodyKey()` calls become a single `withBody([...])` with every key, and `send()` returns `void`. `send()` threw on failures in v2 too, so don't add a `try`/`catch` that changes what happens when publishing fails. Only remove the uses of its return value.
- Consumers: handler signatures use `Junges\Kafka\Contracts\Consumer` instead of `MessageConsumer`, and the renamed builder methods. Most builder methods did not change, see the reference.
- Classes extending the removed abstract `Junges\Kafka\Contracts\Consumer`: implement `Junges\Kafka\Contracts\Handler`, whose method is `__invoke()`, or move their code into consumer classes extending `Junges\Kafka\KafkaConsumer`. When the code moves into a consumer class, delete the old class once nothing references it, and ask the user when it is referenced elsewhere.
- `kafka:consume` invocations: create a consumer class for each one with `php artisan make:kafka-consumer`, move the topics, group, dead letter queue and handler into it, and run it with `php artisan kafka:consume OrdersConsumer`. The argument is the class name in `App\Kafka\Consumers`, or a fully qualified class name for classes in other namespaces. Update the deployment files found in step 2.
- Custom commands wrapping `Kafka::consumer()`: apply the builder renames, and keep their process monitor entries up to date.
- Middleware classes: add the `mixed` return type to `__invoke()`.
- Custom committers, messages, deserializers and serializers: update them to the new contracts.
- Tests: update the expectations of failing handlers, since a failing handler now makes the consumer throw. The fake records published messages when `send()` is called, with both `publish()` and `publishSync()`, so the publishing assertions keep working. Simplifying the `assertPublishedOn('topic', null, fn ...)` calls is optional.

Keep each change minimal and don't refactor unrelated code.

## 7. Verify

1. Run the second search of step 2 again. It must return nothing, except the `.env` files of other environments the user will update when deploying.
2. Run `php artisan about` to make sure the application boots, and `php artisan list kafka` to see the Kafka commands.
3. Run the test suite, and fix the failures caused by the upgrade.
4. Run the project formatter, if any.

## 8. Report

Summarize the changes, and list what the user must do when deploying:

- Set `KAFKA_CONSUMER_GROUP_ID` in every environment, or rely on the fallback of step 5, if they chose to keep the previous group.
- Remove the environment variables v3 no longer reads.
- Deploy the updated Supervisor, or other process monitor, configuration, and restart the consumers with `php artisan kafka:restart-consumers`.
- When consumers stop on failed messages, make sure the process monitor keeps restarting them. With Supervisor, a consumer that stops right after starting counts as a failed start, and it gives up after `startretries`, so use a low `startsecs`:

  ```ini
  [program:orders-consumer]
  command=php artisan kafka:consume OrdersConsumer
  autorestart=true
  startsecs=0
  ```

- Consumers now commit offsets in the background every `auto.commit.interval.ms`, 5 seconds by default, so after a crash the messages processed since the last commit are consumed again. Handlers should be idempotent.
