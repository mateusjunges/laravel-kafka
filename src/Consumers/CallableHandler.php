<?php

namespace Junges\Kafka\Consumers;

use Closure;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class CallableHandler extends Handler
{
    private Closure $handler;
    private array $middlewares;

    public function __construct(callable $handler, array $middlewares)
    {
        $this->handler = Closure::fromCallable($handler);

        $this->middlewares = array_map([$this, 'wrapMiddleware'], $middlewares);
        $this->middlewares[] = $this->wrapMiddleware(
            fn ($message, callable $next) => $next($message)
        );
    }

    /**
     * Handle the received message.
     *
     * @param \Junges\Kafka\Contracts\KafkaConsumerMessage $message
     */
    public function handle(KafkaConsumerMessage $message): void
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
     *
     * @param callable $middleware
     * @return callable
     */
    private function wrapMiddleware(callable $middleware): callable
    {
        return fn (callable $handler) => fn ($message) => $middleware($message, $handler);
    }
}
