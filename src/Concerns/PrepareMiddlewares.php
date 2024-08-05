<?php declare(strict_types=1);

namespace Junges\Kafka\Concerns;

use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Contracts\Middleware;

/** @internal */
trait PrepareMiddlewares
{
    /** Wrap the message with a given middleware. */
    private function wrapMiddleware(Middleware|string|callable $middleware, ?MessageConsumer $consumer = null): callable
    {
        $middleware = match(true) {
            is_string($middleware) && is_subclass_of($middleware, Middleware::class) => new $middleware(),
            $middleware instanceof Middleware => $middleware,
            is_callable($middleware) => $middleware,
            default => throw new \LogicException('Invalid middleware.')
        };

        return static fn (callable $handler) => static fn ($message) => $middleware($message, fn ($message) => $handler($message, $consumer));
    }
}
