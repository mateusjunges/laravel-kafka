<?php

namespace Junges\Kafka\Contracts;

interface Middleware {
    public function __invoke(KafkaConsumerMessage $message, callable $next);
}