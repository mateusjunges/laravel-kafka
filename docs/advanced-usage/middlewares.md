---
title: Middlewares
weight: 5
---

Middlewares provide a convenient way to inspect, filter or transform your Kafka messages before they reach the handler. A middleware receives the message and the next step of the pipeline, and whatever it passes to `$next` is what the handler receives. Not calling `$next` skips the handler for that message, which is then considered handled.

```+parse
<x-sponsors.request-sponsor/>
```

### Writing middlewares

A middleware can be a closure:

```php
use Junges\Kafka\Contracts\ConsumerMessage;

function (ConsumerMessage $message, callable $next) {
    // Perform some work here
    return $next($message);
}
```

Or a class implementing the `Junges\Kafka\Contracts\Middleware` interface. Middleware classes given by name are resolved from the service container, so their constructor can receive any dependency they need:

```php
use Illuminate\Log\LogManager;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Middleware;

class LogMessages implements Middleware
{
    public function __construct(private LogManager $log) {}

    public function __invoke(ConsumerMessage $message, callable $next): mixed
    {
        $result = $next($message);

        $this->log->info('Kafka message handled', [
            'topic' => $message->getTopicName(),
            'offset' => $message->getOffset(),
        ]);

        return $result;
    }
}
```

### Registering middlewares

In a [consumer class](../consuming-messages/class-structure.md), return the middlewares from the `middleware` method:

```php
public function middleware(): array
{
    return [
        LogMessages::class,
        new RequireHeader('tenant'),
        function (ConsumerMessage $message, callable $next) {
            return $next($message);
        },
    ];
}
```

When building a consumer yourself, use the `withMiddleware` method, once for each middleware:

```php
$consumer = \Junges\Kafka\Facades\Kafka::consumer(['orders'])
    ->withMiddleware(LogMessages::class)
    ->withMiddleware(new RequireHeader('tenant'))
    ->withHandler($handler);
```

Middlewares run in the order they are registered, so the first one wraps all the others. When failed messages are [retried](../consuming-messages/handling-failed-messages.md), the middlewares run again on every attempt, and an exception thrown by a middleware makes the message fail like one thrown by the handler.

### Global middlewares

To run middlewares for every consumer, register them with the `consumerMiddleware` method of the `Kafka` facade, usually in the `boot` method of a service provider:

```php
use Junges\Kafka\Facades\Kafka;

public function boot(): void
{
    Kafka::consumerMiddleware([
        LogMessages::class,
        SetTenantFromHeaders::class,
    ]);
}
```

Global middlewares apply to every consumer created through the `Kafka` facade, including [consumer classes](../consuming-messages/class-structure.md), on any connection. They run before the middlewares of each consumer. They are kept when using `Kafka::fake()`, so your tests go through them as well.
