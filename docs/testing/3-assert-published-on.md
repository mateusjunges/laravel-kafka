---
title: Assert published On
weight: 3
---

If you want to assert that a message was published in a specific kafka topic, you can use the `assertPublishedOn` method:

```php
use PHPUnit\Framework\TestCase;
use Junges\Kafka\Facades\Kafka;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();
        
        $producer = Kafka::publishOn('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');
            
        $producer->send();
        
        Kafka::assertPublishedOn('some-kafka-topic', $producer->getMessage());
        
        // Or:
        Kafka::assertPublishedOn('some-kafka-topic');
        
    }
}
```

You can also use a callback function to perform assertions within the message using a callback in which the argument is the published message
itself.

```php
use PHPUnit\Framework\TestCase;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();
        
        $producer = Kafka::publishOn('some-kafka-topic')
            ->withHeaders(['key' => 'value'])
            ->withBodyKey('key', 'value');
            
        $producer->send();
        
        Kafka::assertPublishedOn('some-kafka-topic', $producer->getMessage(), function(Message $message) {
            return $message->getHeaders()['key'] === 'value';
        });
        
        // Or:
        Kafka::assertPublishedOn('some-kafka-topic', null, function(Message $message) {
            return $message->getHeaders()['key'] === 'value';
        });
    }
} 
```