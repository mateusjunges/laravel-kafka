<?php

namespace Junges\Kafka\Consumers;

use Closure;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class CallableConsumer extends Consumer
{
    /**
     * @var \Closure
     */
    private $handler;
    /**
     * @var mixed[]
     */
    private $middlewares;

    public function __construct(callable $handler, array $middlewares)
    {
        $this->handler = \Closure::fromCallable($handler);

        $this->middlewares = array_map([$this, 'wrapMiddleware'], $middlewares);
        $this->middlewares[] = $this->wrapMiddleware(
            function ($message, callable $next) {
                return $next($message);
            }
        );
    }

    /**
     * Handle the received message.
     *
     * @param KafkaConsumerMessage $message
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
        return function (callable $handler) use ($middleware) {
            return function ($message) use ($middleware, $handler) {
                return $middleware($message, $handler);
            };
        };
    }
}
