---
title: Assert published on times
weight: 6
---

To assert that messages were published on a given topic a given number of times, you can use the `assertPublishedOnTimes` method:

```php
use Junges\Kafka\Facades\Kafka;
use Tests\TestCase;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();

        Kafka::publish('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value')
            ->send();

        Kafka::publish('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value')
            ->send();

        Kafka::assertPublishedOnTimes('some-kafka-topic', 2);
    }
}
```

```+parse
<x-sponsors.request-sponsor/>
```
