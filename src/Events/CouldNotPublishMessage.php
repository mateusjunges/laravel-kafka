<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Throwable;

final class CouldNotPublishMessage
{
    public function __construct(
        public readonly int $errorCode,
        public readonly string $message,
        public readonly Throwable $throwable,
    ) {}
}
