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
use Junges\Kafka\Events\MessageSkipped;

Event::listen(function (MessageSkipped $event) {
    logger()->warning('Kafka message skipped', [
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
| `PublishingMessage` | A message is about to be queued on the producer. | `message`, the `ProducerMessage` being published. |
| `MessagePublished` | A message was queued on the producer. It is delivered in the background, so this does not mean Kafka received it. | `message`, the published `ProducerMessage`, with its serialized body. |
| `MessageDeliveryFailed` | A queued message could not be delivered, for instance because its topic does not exist or it was not acknowledged within `message.timeout.ms`. | `topic`, `partition`, `key`, `payload`, `headers`, `errorCode`, `error` and `messageIdentifier`. |
| `CouldNotPublishMessage` | Flushing the producer failed, after its retries. The exception is also thrown, or reported when the flush happens as the application terminates. | `errorCode`, `message` and `throwable`. |

See [producing messages](../producing-messages/producing-messages.md) for when queued messages are flushed.

### Consumer events

| Event | Dispatched when | Properties |
| --- | --- | --- |
| `StartedConsumingMessage` | A message was received, before it is deserialized and handled. | `message`, the received `ConsumerMessage`, with its raw body. |
| `MessageConsumed` | The handler processed a message. | `message`, the `ConsumerMessage` the handler received, with its attempt number. |
| `MessageSentToDLQ` | A failed message was sent to the dead letter queue. | `payload`, `key`, `headers`, `throwable` and `messageIdentifier`. |
| `MessageSkipped` | A failed message was skipped, because the consumer [skips failed messages](../consuming-messages/handling-failed-messages.md#skipping-failed-messages) and has no dead letter queue. | `message`, the `ConsumerMessage` the handler last received, and `throwable`. |

When a message fails and there is neither a dead letter queue nor skipping, no event is dispatched: the consumer stops and `consume()` throws a `Junges\Kafka\Exceptions\ConsumerException`. To be notified of every failed message, whatever happens to it next, use the [failure callback](../consuming-messages/handling-failed-messages.md#being-notified-of-failed-messages).

Faked consumers dispatch the same consumer events, see [mocking your kafka consumer](../testing/mocking-your-kafka-consumer.md).
