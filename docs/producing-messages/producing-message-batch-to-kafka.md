---
title: Producing message batch to kafka
weight: 6
---

```+parse
<x-docs.warning title="This feature is deprecated">
    This is deprecated and will be removed in a future version. Please use 
    <a href="https://laravelkafka.com/docs/v2.0/producing-messages/producing-messages">
        async producers
    </a>
    instead of batch messaging.
</x-docs.warning>
```

You can publish multiple messages at the same time by using message batches.
To use a message batch, you must create a `Junges\Kafka\Producers\MessageBatch` instance.
Then create as many messages as you want and push them to the `MesageBatch` instance.
Finally, create your producer and call the `sendBatch`, passing the `MessageBatch` instance as a parameter.
This is helpful when you persist messages in storage before publishing (e.g. TransactionalOutbox Pattern).

```+parse
<x-sponsors.request-sponsor/>
```

By using message batch, you can send multiple messages using the same producer instance, which is way faster than the default `send` method, which flushes the producer after each produced message.
Messages are queued for asynchronous sending, and there is no guarantee that it will be sent immediately. The `sendBatch` is recommended for a system with high throughput.

```php
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Producers\MessageBatch;
use Junges\Kafka\Message\Message;

$message = new Message(
    headers: ['header-key' => 'header-value'],
    body: ['key' => 'value'],
    key: 'kafka key here',
    topicName: 'my_topic'
)

$messageBatch = new MessageBatch();
$messageBatch->push($message);
$messageBatch->push($message);
$messageBatch->push($message);
$messageBatch->push($message);

/** @var \Junges\Kafka\Producers\Builder $producer */
$producer = Kafka::publish('broker')
    ->onTopic('topic')
    ->withConfigOptions(['key' => 'value']);

$producer->sendBatch($messageBatch);
```

When producing batch messages, you can specify the topic for each message that you want to publish. If you want to publish all messages in the same topic,
you can use the `onTopic` method on the `MessageBatch` class to specify the topic once for all messages. Please note that
if a message within the batch specifies a topic, we will use that topic to publish the message, instead of the topic defined on the 
batch itself.