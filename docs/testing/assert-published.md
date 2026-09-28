---
title: Assert Published
weight: 2
---

When you want to assert that a message was published into kafka, you can make use of the `assertPublished` method:

```php
use Junges\Kafka\Facades\Kafka;
use Tests\TestCase;

class MyTest extends TestCase
{
     public function testMyAwesomeApp()
     {
         Kafka::fake();
         
         $producer = Kafka::publish('topic')
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
use Tests\TestCase;

class MyTest extends TestCase
{
     public function testMyAwesomeApp()
     {
         Kafka::fake();
         
         Kafka::publish('topic')
             ->withHeaders(['key' => 'value'])
             ->withBodyKey('foo', 'bar')
             ->send();

         Kafka::assertPublished();       
     }
}
```

To check the published message yourself, pass a callback receiving each published message and returning whether it matches:

```php
use Junges\Kafka\Contracts\ProducerMessage;

Kafka::assertPublished(function (ProducerMessage $message) {
    return $message->getBody()['foo'] === 'bar';
});
```

When you pass both an expected message and a callback, a published message must match both.

```+parse
<x-sponsors.request-sponsor/>
```