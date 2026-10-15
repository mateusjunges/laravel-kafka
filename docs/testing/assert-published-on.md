---
title: Assert published On
weight: 3
---

```+parse
<x-sponsors.request-sponsor/>
```

If you want to assert that a message was published in a specific kafka topic, you can use the `assertPublishedOn` method:

```php
use Tests\TestCase;
use Junges\Kafka\Facades\Kafka;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();
        
        $producer = Kafka::publish('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBody(['key' => 'value']);
            
        $producer->send();
        
        Kafka::assertPublishedOn('some-kafka-topic', $producer->getMessage());
        
        // Or:
        Kafka::assertPublishedOn('some-kafka-topic');
        
    }
}
```

You can also pass a callback, which receives each message published on the topic and returns whether it matches:

```php
use Tests\TestCase;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();
        
        $producer = Kafka::publish('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBody(['key' => 'value']);
            
        $producer->send();
        
        Kafka::assertPublishedOn('some-kafka-topic', function (Message $message) {
            return $message->getHeaders()['key'] === 'value';
        });

        // The expected message and a callback can also be combined, a message must match both:
        Kafka::assertPublishedOn('some-kafka-topic', $producer->getMessage(), function (Message $message) {
            return $message->getHeaders()['key'] === 'value';
        });
    }
} 
```