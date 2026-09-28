<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Closure;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\Middleware;
use LogicException;
use Throwable;

/** Passes each consumed message through the middlewares to the handler. */
final class MessageHandler
{
    private readonly Closure|Handler $handler;

    /**
     * @param  list<Middleware|callable|class-string<Middleware>>  $middlewares
     * @param  (Closure(ConsumerMessage, Throwable): void)|null  $onFailure
     */
    public function __construct(
        Closure|Handler $handler,
        private readonly array $middlewares = [],
        private readonly ?Closure $onFailure = null,
    ) {
        $this->handler = $handler;
    }

    public function handle(ConsumerMessage $message, Consumer $consumer): void
    {
        $handler = $this->handler;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $handler = $this->wrapMiddleware($middleware, $consumer)($handler);
        }

        $handler($message, $consumer);
    }

    /** Notify that a message failed, once its retries are used. */
    public function failed(ConsumerMessage $message, Throwable $exception): void
    {
        if ($this->onFailure instanceof Closure) {
            ($this->onFailure)($message, $exception);
        }
    }

    private function wrapMiddleware(Middleware|string|callable $middleware, Consumer $consumer): callable
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
