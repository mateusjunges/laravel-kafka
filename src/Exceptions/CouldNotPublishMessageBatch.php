<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions;

final class CouldNotPublishMessageBatch extends LaravelKafkaException
{
    public static function invalidTopicName(string $topic): self
    {
        $message = match (true) {
            $topic === '' => "The provided topic name [''] is invalid for the message batch. Try again with a valid topic name.",
            default => 'The provided topic name is invalid for the message batch. Try again with a valid topic name.'
        };

        return new self($message);
    }
}
