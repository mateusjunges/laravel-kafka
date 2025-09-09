<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Closure;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Contracts\Middleware;
use LogicException;

class CallableConsumer extends Consumer
{
    public function __construct(private Closure|Handler $handler, private readonly array $middlewares)
    {
        $this->handler = match (true) {
            $handler instanceof Handler => $handler,
            default => $handler(...),
        };
    }

    public function handle(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $this->handleConsumedMessage($message, $this->handler, $consumer, $this->middlewares);
    }

    private function handleConsumedMessage(ConsumerMessage $message, Handler|Closure $handler, ?MessageConsumer $consumer = null, array $middlewares = []): void
    {
        $middlewares = array_map(fn ($middleware) => $this->wrapMiddleware($middleware, $consumer), $middlewares);
        $middlewares = array_reverse($middlewares);

        foreach ($middlewares as $middleware) {
            $handler = $middleware($handler);
        }

        $handler($message, $consumer);
    }

    private function wrapMiddleware(Middleware|string|callable $middleware, ?MessageConsumer $consumer = null): callable
    {
        $middleware = match (true) {
            is_string($middleware) && is_subclass_of($middleware, Middleware::class) => new $middleware,
            $middleware instanceof Middleware => $middleware,
            is_callable($middleware) => $middleware,
            default => throw new LogicException('Invalid middleware.')
        };

        return static fn (callable $handler) => static fn ($message) => $middleware($message, fn ($message) => $handler($message, $consumer));
    }
}
