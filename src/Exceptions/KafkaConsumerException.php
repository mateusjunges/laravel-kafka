<?php

namespace Junges\Kafka\Exceptions;

class KafkaConsumerException extends LaravelKafkaException
{
    public static function dlqCanNotBeSetWithoutSubscribingToAnyTopics(): self
    {
        return new static("You must subscribe to a kafka topic before specifying the DLQ.");
    }
}
