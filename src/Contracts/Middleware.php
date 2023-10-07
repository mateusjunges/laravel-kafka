<?php

namespace Junges\Kafka\Contracts;

interface Middleware
{
    public function __invoke(ConsumerMessage $message, callable $next);
}
