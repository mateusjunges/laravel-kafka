<?php

namespace Junges\Kafka\Producers;

use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Message\Message;
use SplDoublyLinkedList;

/**
 * Class stores multiple messages to produce them to kafka topic as a batch
 *
 * @see CanProduceMessages::sendBatch
 */
class MessageBatch
{
    /** Storage of messages*/
    private SplDoublyLinkedList $messages;

    #[Pure]
    public function __construct()
    {
        $this->messages = new SplDoublyLinkedList();
    }

    /** Pushes messages to batch*/
    public function push(Message $message): void
    {
        $this->messages->push($message);
    }

    /** Returns all messages from batch before producing them to kafka */
    public function getMessages(): SplDoublyLinkedList
    {
        return $this->messages;
    }
}
