<?php declare(strict_types=1);

namespace Junges\Kafka\Events;

use Throwable;

final readonly class CouldNotPublishMessage
{
    public function __construct(
        public int $errorCode,
        public string $message,
        public Throwable $throwable,
    ) {}
}
