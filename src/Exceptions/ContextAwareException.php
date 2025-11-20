<?php

declare(strict_types=1);

namespace Junges\Kafka\Exceptions;

use Exception;
use Junges\Kafka\Contracts\ContextAware;
use Throwable;

class ContextAwareException extends Exception implements ContextAware
{
    public function __construct(private readonly array $context = [], string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
