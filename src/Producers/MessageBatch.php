<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Illuminate\Support\Str;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use SplDoublyLinkedList;

/**
 * Class stores multiple messages to produce them to kafka topic as a batch
 *
 * @see MessageProducer::sendBatch
 * @deprecated Please use {@see Kafka::asyncPublish()} instead of batch messaging.
 */
class MessageBatch
{
    /** Storage of messages */
    private readonly SplDoublyLinkedList $messages;

    private string $uuid;

    private string $topic = '';

    public function __construct()
    {
        $this->messages = new SplDoublyLinkedList();
        $this->uuid = Str::uuid()->toString();
    }

    /** Pushes messages to batch */
    public function push(Message $message): void
    {
        $this->messages->push($message);
    }

    public function onTopic(string $topicName): self
    {
        $this->topic = $topicName;

        return $this;
    }

    /**
     * Returns all messages from batch before producing them to kafka
     *
     * @return SplDoublyLinkedList<\Junges\Kafka\Message\Message>
     */
    public function getMessages(): SplDoublyLinkedList
    {
        return $this->messages;
    }

    public function getBatchUuid(): string
    {
        return $this->uuid;
    }

    public function getTopicName(): string
    {
        return $this->topic;
    }
}
