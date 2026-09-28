<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions;

use RdKafka\Message;
use Throwable;

class ConsumerException extends LaravelKafkaException
{
    public static function dlqCanNotBeSetWithoutSubscribingToAnyTopics(): self
    {
        return new static('You must subscribe to a kafka topic before specifying the DLQ.');
    }

    public static function stoppedOnFailure(Message $message, Throwable $throwable): self
    {
        return new static(
            "Stopped consuming after the message at offset [{$message->offset}] of topic [{$message->topic_name}] partition [{$message->partition}] failed. Its offset was not committed.",
            previous: $throwable
        );
    }
}
