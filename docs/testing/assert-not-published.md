---
title: Assert not published
weight: 4
---

To assert that a given message was not published, use the `assertNotPublished` method. Like `assertPublished`, it accepts an expected message, a callback, or both:

```php
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Facades\Kafka;
use Tests\TestCase;

class MyTest extends TestCase
{
    public function testCancelledOrdersAreNotPublished()
    {
        Kafka::fake();

        $this->post('/orders/1/cancel');

        Kafka::assertNotPublished(function (ProducerMessage $message) {
            return $message->getTopicName() === 'orders' && $message->getBody()['id'] === 1;
        });
    }
}
```

To assert that nothing was published on a topic, use the `assertNothingPublishedOn` method:

```php
Kafka::assertNothingPublishedOn('orders');
```

```+parse
<x-sponsors.request-sponsor/>
```
