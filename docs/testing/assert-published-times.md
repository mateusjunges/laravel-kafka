---
title: Assert published times
weight: 5
---

Sometimes, you need to assert that Kafka has published a given number of messages. For that, you can use the `assertPublishedTimes` method:

```php
use Junges\Kafka\Facades\Kafka;
use Tests\TestCase;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();

        Kafka::publish('topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value')
            ->send();

        Kafka::publish('topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value')
            ->send();

        Kafka::assertPublishedTimes(2);
    }
}
```

Like `assertPublished`, it also accepts the expected message and a callback, to count only the matching messages.

```+parse
<x-sponsors.request-sponsor/>
```
