<?php declare(strict_types=1);

namespace Junges\Kafka\Exceptions;

use Junges\Kafka\Contracts\ConsumerMessage;
use RdKafka\Message;
use Throwable;

class ConsumerException extends LaravelKafkaException
{
    public static function dlqCanNotBeSetWithoutSubscribingToAnyTopics(): self
    {
        return new static('The dead letter queue can only be named after the consumed topic when the consumer subscribes to a topic or is assigned partitions. Pass the dead letter queue topic to withDlq() instead.');
    }

    public static function stoppedOnFailure(Message|ConsumerMessage $message, Throwable $throwable): self
    {
        [$topic, $partition, $offset] = $message instanceof Message
            ? [$message->topic_name, $message->partition, $message->offset]
            : [$message->getTopicName(), $message->getPartition(), $message->getOffset()];

        return new static(
            "Stopped consuming after the message at offset [{$offset}] of topic [{$topic}] partition [{$partition}] failed. Its offset was not committed.",
            previous: $throwable
        );
    }
}
