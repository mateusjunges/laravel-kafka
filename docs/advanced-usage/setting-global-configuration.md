---
title: Setting global configurations
weight: 8
---

Configuration shared by every producer and consumer of a Kafka cluster belongs to its connection, in your `config/kafka.php` file. See the [connections](/advanced-usage/connections) documentation for the available options.

If you publish the same kind of message in many places, you can also use the `macro` method of the `Kafka` facade to define it once:

```php
// In a service provider:

\Junges\Kafka\Facades\Kafka::macro('orderShipped', function (Order $order) {
    return $this->publish('orders')
        ->withKey((string) $order->id)
        ->withHeaders(['event' => 'order-shipped']);
});
```

Now, you can call `\Junges\Kafka\Facades\Kafka::orderShipped($order)->withBody($order->toArray())->send()`.

```+parse
<x-sponsors.request-sponsor/>
```
