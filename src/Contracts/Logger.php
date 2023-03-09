<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

use RdKafka\Message;
use Throwable;

interface Logger
{
    public function error(Message $message, Throwable $e = null, string $prefix = 'ERROR'): void;
}
