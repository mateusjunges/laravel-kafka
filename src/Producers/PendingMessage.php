<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Illuminate\Support\Traits\Conditionable;
use Junges\Kafka\Connection;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\ProducerMessage;
use LogicException;

class PendingMessage
{
    use Conditionable;

    private ProducerMessage $message;

    private ?MessageSerializer $serializer = null;

    public function __construct(
        private readonly Connection $connection,
        private ?string $topic = null,
        private readonly bool $sync = false,
    ) {
        /** @var ProducerMessage $message */
        $message = app(ProducerMessage::class);
        $this->message = $message::create($topic);
    }

    /** Set the topic the message is published to. */
    public function onTopic(string $topic): self
    {
        $this->topic = $topic;
        $this->message->onTopic($topic);

        return $this;
    }

    /** Set the message key. */
    public function withKey(mixed $key): self
    {
        $this->message->withKey($key);

        return $this;
    }

    /** Set the message body. */
    public function withBody(mixed $body): self
    {
        $this->message->withBody($body);

        return $this;
    }

    /** Set a key of the message body. */
    public function withBodyKey(string $key, mixed $value): self
    {
        $this->message->withBodyKey($key, $value);

        return $this;
    }

    /** Set the message headers. */
    public function withHeaders(array $headers = []): self
    {
        $this->message->withHeaders($headers);

        return $this;
    }

    /** Set a single message header. */
    public function withHeader(string $key, string|int|float $value): self
    {
        $this->message->withHeader($key, $value);

        return $this;
    }

    /** Replace the message being built with the given one. */
    public function withMessage(ProducerMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

    /** Serialize this message using the given serializer instead of the default one. */
    public function usingSerializer(MessageSerializer $serializer): self
    {
        $this->serializer = $serializer;

        return $this;
    }

    public function getMessage(): ProducerMessage
    {
        return $this->message;
    }

    /**
     * Send the message. Asynchronous messages are queued on the connection's producer
     * and delivered in the background, synchronous ones are flushed right away.
     *
     * @throws \Junges\Kafka\Exceptions\CouldNotPublishMessage
     */
    public function send(): void
    {
        if ($this->message->getTopicName() === null && $this->topic !== null) {
            $this->message->onTopic($this->topic);
        }

        if (blank($this->message->getTopicName())) {
            throw new LogicException('The message can not be published without a topic. Use the onTopic() method to set one.');
        }

        $producer = $this->connection->producer();

        $producer->produce($this->message, $this->serializer);

        if ($this->sync) {
            $producer->flush();
        }
    }
}
