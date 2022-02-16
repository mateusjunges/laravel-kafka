---
title: Middlewares
weight: 5
---

Middlewares provides a convenient way to filter and inspecting your Kafka messages. To write a middleware in this package, you can
use the `withMiddleware` method. The middleware is a callable in which the first argument is the message itself and the second one is
the next handler. The middlewares get executed in the order they are defined:

```php
$consumer = \Junges\Kafka\Facades\Kafka::createConsumer()
    ->withMiddleware(function($message, callable $next) {
        // Perform some work here
        return $next($message);
    });
```

You can add as many middlewares as you need, so you can divide different tasks into different middlewares.