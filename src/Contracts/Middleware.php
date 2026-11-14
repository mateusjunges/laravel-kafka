<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface Middleware
{
    /**
     * Handle the message, passing it to the next middleware, or to the handler, by calling $next.
     * Whatever is passed to $next is what the next middleware or the handler receives.
     */
    public function __invoke(ConsumerMessage $message, callable $next): mixed;
}
