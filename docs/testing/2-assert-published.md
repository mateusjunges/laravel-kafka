---
title: Assert Published
weight: 2
---

When you want to assert that a message was published into kafka, you can make use of the `assertPublished` method:

```php
use Junges\Kafka\Facades\Kafka;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
     public function testMyAwesomeApp()
     {
         Kafka::fake();
         
         $producer = Kafka::publishOn('topic')
             ->withHeaders(['key' => 'value'])
             ->withBodyKey('foo', 'bar');
             
         $producer->send();
             
         Kafka::assertPublished($producer->getMessage());       
     }
}
```

You can also use `assertPublished` without passing the message argument:

```php
use Junges\Kafka\Facades\Kafka;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
     public function testMyAwesomeApp()
     {
         Kafka::fake();
         
         Kafka::publishOn('topic')
             ->withHeaders(['key' => 'value'])
             ->withBodyKey('foo', 'bar');
             
             
         Kafka::assertPublished();       
     }
}
```