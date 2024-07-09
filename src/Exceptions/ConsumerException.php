<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions;

use RdKafka\Message;

class ConsumerException extends LaravelKafkaException
{
    protected Message $kafkaMessage;

    public static function dlqCanNotBeSetWithoutSubscribingToAnyTopics(): self
    {
        return new static("You must subscribe to a kafka topic before specifying the DLQ.");
    }

    public function setKafkaMessage(Message $kafkaMessage)
    {
        $this->kafkaMessage = $kafkaMessage;
    }

    public function getKafkaMessage(): Message
    {
        return $this->kafkaMessage;
    }
}
