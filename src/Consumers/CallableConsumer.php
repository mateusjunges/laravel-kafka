<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Closure;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;

class CallableConsumer extends Consumer
{
    private readonly Closure $handler;
    private array $middlewares;

    public function __construct(callable $handler, array $middlewares)
    {
        $this->handler = Closure::fromCallable($handler);

        $this->middlewares = array_map($this->wrapMiddleware(...), $middlewares);
        $this->middlewares[] = $this->wrapMiddleware(
            fn ($message, callable $next) => $next($message)
        );
    }

    /**
     * Handle the received message.
     */
    public function handle(ConsumerMessage $message): void
    {
        $middlewares = array_reverse($this->middlewares);
        $handler = array_shift($middlewares)($this->handler);

        foreach ($middlewares as $middleware) {
            $handler = $middleware($handler);
        }

        $handler($message);
    }

    /**
     * Wrap the message with a given middleware.
     */
    private function wrapMiddleware(callable $middleware): callable
    {
        return fn (callable $handler) => fn ($message) => $middleware($message, $handler);
    }
}
