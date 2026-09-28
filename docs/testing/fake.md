---
title: Kafka fake
weight: 1
---

When testing your application, you usually don't want to publish or consume real Kafka messages. Calling `Kafka::fake()` replaces the Kafka manager with a fake, so no message leaves your application, and you can make assertions about the messages it published:

```php
use Junges\Kafka\Facades\Kafka;

public function test_orders_are_published()
{
    Kafka::fake();

    $this->post('/orders', ['product' => 'book']);

    Kafka::assertPublishedOn('orders', callback: function ($message) {
        return $message->getBody()['product'] === 'book';
    });
}
```

```+parse
<x-sponsors.request-sponsor/>
```

The fake replaces every [connection](../advanced-usage/connections.md), so messages published with `publish`, `publishSync` or through `Kafka::connection('name')` are all recorded, and connections that are not configured can be used without a configuration. Global [middlewares](../advanced-usage/middlewares.md) are kept, so faked consumers go through them like real ones.

The following assertions are available:

- [`assertPublished`](assert-published.md): a message was published.
- [`assertPublishedOn`](assert-published-on.md): a message was published on a topic.
- [`assertPublishedTimes`](assert-published-times.md): a number of messages were published.
- [`assertPublishedOnTimes`](assert-published-on-times.md): a number of messages were published on a topic.
- [`assertNothingPublished`](assert-nothing-published.md): no message was published.

They accept the expected message, a callback receiving each published message and returning whether it matches, or both. When comparing messages, their [ids](../producing-messages/configuring-message-payload.md#message-ids) are ignored, since every message gets its own.

To test consumers, including [consumer classes](../consuming-messages/class-structure.md), see [mocking your kafka consumer](mocking-your-kafka-consumer.md).
