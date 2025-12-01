<?php

declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface ContextAware
{
    /**
     * Return the context as an associative array.
     *
     * @return array<string, string>
     */
    public function getContext(): array;
}
