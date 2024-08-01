<?php declare(strict_types=1);

namespace Junges\Kafka\Concerns;

use Closure;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;

/**
 * @internal
 * @mixin \Junges\Kafka\Concerns\PrepareMiddlewares
 */
trait HandleConsumedMessage
{
    private function handleConsumedMessage(ConsumerMessage $message, Handler|Closure $handler, ?MessageConsumer $consumer = null, array $middlewares = []): void
    {
        $middlewares = array_map(fn ($middleware) => $this->wrapMiddleware($middleware, $consumer), $middlewares);
        $middlewares = array_reverse($middlewares);

        foreach ($middlewares as $middleware) {
            $handler = $middleware($handler);
        }

        $handler($message, $consumer);
    }
}
