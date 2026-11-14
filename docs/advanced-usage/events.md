---
title: Events
weight: 10
---

The package dispatches Laravel events while publishing and consuming messages, which you can listen to for logging, monitoring or metrics. All of them are in the `Junges\Kafka\Events` namespace.

```+parse
<x-sponsors.request-sponsor/>
```

```php
use Illuminate\Support\Facades\Event;
use Junges\Kafka\Events\MessageFailed;

Event::listen(function (MessageFailed $event) {
    logger()->warning('Kafka message failed', [
        'consumer' => $event->consumer->getName(),
        'topic' => $event->message->getTopicName(),
        'offset' => $event->message->getOffset(),
        'exception' => $event->throwable->getMessage(),
    ]);
});
```

Every event about a single message has a `getMessageIdentifier()` method, returning the [id of the message](../producing-messages/configuring-message-payload.md#message-ids), so you can follow a message across events and applications.

### Producer events

| Event | Dispatched when | Properties |
| --- | --- | --- |
| `PublishingMessage` | A message is about to be queued on the producer. | `message`, the `ProducerMessage` being published, and `connection`. |
| `MessagePublished` | A message was queued on the producer. It is delivered in the background, so this does not mean Kafka received it. | `message`, the published `ProducerMessage`, with its serialized body, and `connection`. |
| `MessageDelivered` | Kafka acknowledged a queued message. | `topic`, `partition`, `offset`, `key`, `messageIdentifier` and `connection`. |
| `MessageDeliveryFailed` | A queued message could not be delivered, for instance because its topic does not exist or it was not acknowledged within `message.timeout.ms`. | `topic`, `partition`, `key`, `payload`, `headers`, `errorCode`, `error`, `messageIdentifier` and `connection`. |
| `CouldNotPublishMessage` | Flushing the producer failed, after its retries. The exception is also thrown, or reported when the flush happens as the application terminates. | `errorCode`, `message`, `throwable` and `connection`. |

The `connection` of the producer events is the name of the connection the message was published on, as configured in `config/kafka.php`, so listeners can tell the clusters of an application apart, or publish a message again on the same one.

Delivery reports are received while the producer publishes or flushes messages, so `MessageDelivered` and `MessageDeliveryFailed` are usually dispatched when the messages are flushed. See [producing messages](../producing-messages/producing-messages.md) for when queued messages are flushed.

### Consumer events

Every consumer event has a `consumer` property, the `Junges\Kafka\Contracts\Consumer` that dispatched it. Its `getName()`, `getConnectionName()`, `getGroupId()` and `getTopics()` methods tell which consumer it is, see [naming consumers](../consuming-messages/class-structure.md#naming-consumers).

| Event | Dispatched when | Other properties |
| --- | --- | --- |
| `ConsumerStarting` | The consumer starts consuming, before it connects to Kafka. | |
| `PartitionsAssigned` | Partitions were assigned to the consumer, on a consumer group rebalance. | `partitions`, a list of `RdKafka\TopicPartition`. |
| `PartitionsRevoked` | Partitions were revoked from the consumer, on a consumer group rebalance. | `partitions`, a list of `RdKafka\TopicPartition`. |
| `StartedConsumingMessage` | A message was received, before it is deserialized and handled. | `message`, the received `ConsumerMessage`, with its raw body. |
| `MessageConsumed` | The handler processed a message. | `message`, the `ConsumerMessage` the handler received, with its attempt number. |
| `RetryingMessage` | The handler failed and the message will be [retried](../consuming-messages/handling-failed-messages.md#retrying-failed-messages), after the backoff. | `message`, with the number of the attempt that failed, and `throwable`. |
| `MessageFailed` | A message is handled as failed, once its retries are used, before it is sent to the dead letter queue, skipped, or stops the consumer. | `message`, the `ConsumerMessage` the handler last received, and `throwable`. A message that can't be deserialized fails right away, and has its raw body. |
| `MessageSentToDLQ` | A failed message was sent to the dead letter queue. | `message`, the consumed `ConsumerMessage`, `throwable`, `topic`, the dead letter queue, and `payload`, `key` and `headers`, as published to the dead letter queue. |
| `MessageSkipped` | A failed message was skipped, because the consumer [skips failed messages](../consuming-messages/handling-failed-messages.md#skipping-failed-messages) and has no dead letter queue. | `message`, the `ConsumerMessage` the handler last received, and `throwable`. |
| `OffsetsCommitted` | Offsets were committed, either in the background with auto commit, or through `commit()` and `commitAsync()`. | `partitions`, a list of `RdKafka\TopicPartition` holding the committed offsets. |
| `OffsetCommitFailed` | Offsets could not be committed. | `partitions`, `errorCode` and `error`. |
| `ConsumerStopped` | The consumer stopped consuming and left its consumer group. | `reason`, a `Junges\Kafka\Consumers\StopReason`, and `exception`, the exception that stopped the consumer, if any. |

`ConsumerStopped` is dispatched whether the consumer stopped normally or because of an exception, which `consume()` throws right after the event. Its `reason` tells why the consumer stopped:

| Reason | The consumer stopped because |
| --- | --- |
| `StopReason::Requested` | It was asked to stop through [`stopConsuming()`](stopping-a-consumer.md). |
| `StopReason::Signal` | The process received a `SIGTERM`, `SIGINT` or `SIGQUIT` signal, see [graceful shutdown](graceful-shutdown.md). |
| `StopReason::Restart` | Consumers were asked to restart through the [`kafka:restart-consumers`](running-consumers-in-production.md#restarting-consumers-after-deployments) command. |
| `StopReason::Empty` | There were no messages left in its partitions, with [`stopWhenEmpty()`](stop-consumer-after-last-message.md). |
| `StopReason::MessageLimit` | It handled the number of messages given to `stopAfterMessages()`. |
| `StopReason::TimeLimit` | It ran for the number of seconds given to `stopAfterSeconds()`. |
| `StopReason::Failed` | An exception was thrown while consuming, for instance by a failed message. |

Faked consumers dispatch the same message events, along with `ConsumerStarting` and `ConsumerStopped`, see [mocking your kafka consumer](../testing/mocking-your-kafka-consumer.md).

### Client events

These events are dispatched for librdkafka reports that are not tied to a single message. They have a `connection` property, the name of the connection, and a `consumer` property, the consumer that reported them, or `null` when they were reported by a producer.

| Event | Dispatched when | Other properties |
| --- | --- | --- |
| `StatisticsReported` | librdkafka reported its statistics, every `statistics.interval.ms`. | `statistics`, the decoded [statistics](https://github.com/confluentinc/librdkafka/blob/master/STATISTICS.md), including the lag of each partition of a consumer. |
| `KafkaErrorOccurred` | librdkafka reported an error, such as brokers that can't be reached. It recovers from most of them by itself. | `errorCode` and `error`. |

Statistics are disabled by default. Enable them by setting the `statistics.interval.ms` option of a connection, in milliseconds. Consumers report them while consuming, and producers while publishing or flushing messages.

librdkafka logs its errors when nothing receives them, so the `KafkaErrorOccurred` event is only dispatched when it has listeners by the time the consumer or producer is created. Register its listeners in the `boot` method of a service provider.

The `onStatistics()`, `onError()`, `onRebalance()` and `onOffsetCommit()` [configuration callbacks](../consuming-messages/configuring-consumer-options.md) are still called when these events are dispatched, so you can use both.
