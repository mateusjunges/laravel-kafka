<?php declare(strict_types=1);

namespace Junges\Kafka\Concerns;

use Closure;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;

/**
 * @internal
 * @mixin \Junges\Kafka\Concerns\PrepareMiddlewares
 */
trait HandleConsumedMessage
{
    private function handleConsumedMessage(ConsumerMessage $message, Handler|Closure $handler, array $middlewares = []): void
    {
        $middlewares = array_map($this->wrapMiddleware(...), $middlewares);
        $middlewares = array_reverse($middlewares);

        foreach ($middlewares as $middleware) {
            $handler = $middleware($handler);
        }

        $handler($message);
    }
}