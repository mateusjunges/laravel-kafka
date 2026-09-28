---
title: Setting global configurations
weight: 8
---

Configuration shared by every producer and consumer of a Kafka cluster belongs to its connection, in your `config/kafka.php` file. See the [connections](/advanced-usage/connections) documentation for the available options.

## Configuring every consumer

To apply the same configuration to every consumer, register a callback with the `configureConsumersUsing` method of the `Kafka` facade, usually in the `boot` method of a service provider. It receives the builder of each consumer when it is created, including [consumer classes](../consuming-messages/class-structure.md), on any connection:

```php
use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Facades\Kafka;

public function boot(): void
{
    Kafka::configureConsumersUsing(function (Builder $builder) {
        $builder
            ->withOption('statistics.interval.ms', 10000)
            ->beforeConsuming(function (Consumer $consumer) {
                // Runs before each message is consumed, by every consumer
            });
    });
}
```

The callback runs before each consumer is configured, so the configuration of each consumer takes precedence. It only applies to consumers created through the `Kafka` facade, and it is kept when using `Kafka::fake()`. To add [middlewares](middlewares.md#global-middlewares) to every consumer, use the `consumerMiddleware` method instead.

## Macros

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
