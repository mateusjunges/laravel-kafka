<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions;

class ConsumerException extends LaravelKafkaException
{
    public static function dlqCanNotBeSetWithoutSubscribingToAnyTopics(): self
    {
        return new static("You must subscribe to a kafka topic before specifying the DLQ.");
    }
}
