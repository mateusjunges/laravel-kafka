<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions;

final class MessageIdNotSet extends LaravelKafkaException
{
    protected $message = 'The message identifier was not set.';
}
