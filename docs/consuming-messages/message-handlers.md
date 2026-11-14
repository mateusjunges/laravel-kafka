---
title: Message handlers
weight: 5
---

Now that you have created your kafka consumer, you must create a handler for the messages it receives. A handler receives the consumed message and the consumer, and it can be a closure, an invokable class, or a class implementing the `Junges\Kafka\Contracts\Handler` interface. Use the `withHandler` method to specify your handler:

```php
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;

$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->withHandler(function (ConsumerMessage $message, Consumer $consumer) {
        // Handle your message here
    });
```

To keep a consumer and its configuration in a single class, see [consumer classes](class-structure.md).

```+parse
<x-sponsors.request-sponsor/>
```

## The consumed message

The `ConsumerMessage` contract gives you some handy methods to get the message properties:

- `getBody()`: the body of the message. With the default JSON deserializer, it is already decoded into an array.
- `getKey()`: the key of the message.
- `getHeaders()`: the headers of the message.
- `getTopicName()`: the topic the message was consumed from.
- `getPartition()`: the partition the message was consumed from.
- `getOffset()`: the offset of the message in its partition.
- `getTimestamp()`: the timestamp of the message, in milliseconds.
- `getMessageIdentifier()`: the id of the message. See [message ids](../producing-messages/configuring-message-payload.md#message-ids).
- `getAttempts()`: how many times the handler was called with this message, including the current call, when failed messages are [retried](handling-failed-messages.md).

## Handler classes

Handler classes implement the `Handler` interface:

```php
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;

class ProcessOrderHandler implements Handler
{
    public function __invoke(ConsumerMessage $message, Consumer $consumer): void
    {
        $order = $message->getBody();

        // Process the order
    }
}

$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->withHandler(new ProcessOrderHandler)
    ->build();

$consumer->consume();
```

## Failures

When the handler throws an exception, the message is handled as failed: it is retried if the consumer [retries failed messages](handling-failed-messages.md#retrying-failed-messages), and then sent to the dead letter queue, skipped, or stops the consumer, depending on the configuration. Let exceptions propagate from your handler, since catching them without rethrowing makes the consumer move on as if the message was processed.

## Committing from handlers

The consumer passed to handlers can commit offsets itself, which is useful in [manual commit](../advanced-usage/manual-commit.md) mode:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->withManualCommit()
    ->withHandler(function (ConsumerMessage $message, Consumer $consumer) {
        processOrder($message->getBody());

        $consumer->commit($message);
    });
```

It can also stop the consumer, see [stopping a consumer on demand](../advanced-usage/stopping-a-consumer.md).
