---
title: Assert published on times
weight: 6
---

To assert that messages were published on a given topic a given number of times, you can use the `assertPublishedOnTimes` method:
```php
use PHPUnit\Framework\TestCase;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();

        Kafka::publishOn('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');

        Kafka::publishOn('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');

        Kafka::assertPublishedOnTimes('some-kafka-topic', 2);
    }
} 
```