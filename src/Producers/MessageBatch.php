<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Illuminate\Support\Str;
use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Message\Message;
use SplDoublyLinkedList;

/**
 * Class stores multiple messages to produce them to kafka topic as a batch
 *
 * @see MessageProducer::sendBatch
 */
class MessageBatch
{
    /** Storage of messages */
    private readonly SplDoublyLinkedList $messages;

    private string $uuid;

    #[Pure]
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
}
