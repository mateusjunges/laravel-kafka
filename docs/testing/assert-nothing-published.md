---
title: Assert Nothing Published
weight: 4
---

You can assert that nothing was published at all, using the `assertNothingPublished`:

```php
use PHPUnit\Framework\TestCase;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class MyTest extends TestCase
{
    public function testWithSpecificTopic()
    {
        Kafka::fake();
        
        if (false) {
            $producer = Kafka::publishOn('cluster')
                ->onTopic('some-kafka-topic')
                ->withHeaders(['key' => 'value'])
                ->withBodyKey('key', 'value');
                
            $producer->send();
        }
        
        Kafka::assertNothingPublished();
    }
} 
```
