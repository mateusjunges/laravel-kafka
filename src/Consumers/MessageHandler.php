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

    /** The handler wrapped by the middlewares, built on the first message and reused for the next ones. */
    private ?Closure $pipeline = null;

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
        ($this->pipeline ??= $this->buildPipeline())($message, $consumer);
    }

    /** Notify that a message failed, once its retries are used. */
    public function failed(ConsumerMessage $message, Throwable $exception): void
    {
        if ($this->onFailure instanceof Closure) {
            ($this->onFailure)($message, $exception);
        }
    }

    /** Wrap the handler in the middlewares, so the first middleware runs first. */
    private function buildPipeline(): Closure
    {
        $pipeline = Closure::fromCallable($this->handler);

        foreach (array_reverse($this->middlewares) as $middleware) {
            $middleware = $this->resolveMiddleware($middleware);
            $next = $pipeline;

            $pipeline = static fn (mixed $message, Consumer $consumer): mixed => $middleware(
                $message,
                static fn (mixed $message): mixed => $next($message, $consumer),
            );
        }

        return $pipeline;
    }

    private function resolveMiddleware(Middleware|string|callable $middleware): callable
    {
        return match (true) {
            is_string($middleware) && is_subclass_of($middleware, Middleware::class) => app($middleware),
            $middleware instanceof Middleware, is_callable($middleware) => $middleware,
            default => throw new LogicException('Invalid middleware.'),
        };
    }
}
