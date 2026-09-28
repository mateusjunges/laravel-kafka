---
title: Assert nothing published
weight: 4
---

You can assert that nothing was published at all, using the `assertNothingPublished` method:

```php
use Junges\Kafka\Facades\Kafka;
use Tests\TestCase;

class MyTest extends TestCase
{
    public function testNothingIsPublishedForInvalidOrders()
    {
        Kafka::fake();

        $this->post('/orders', ['product' => null]);

        Kafka::assertNothingPublished();
    }
}
```

```+parse
<x-sponsors.request-sponsor/>
```
