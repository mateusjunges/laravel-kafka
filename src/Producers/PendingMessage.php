<?php declare(strict_types=1);

namespace Junges\Kafka\Producers;

use Closure;
use Illuminate\Support\Traits\Conditionable;
use Junges\Kafka\Connection;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\ProducerMessage;
use LogicException;

/**
 * Builds a message to publish. Changes are recorded and applied, in order, to a copy of the message when it is
 * sent, so they are kept when the message is replaced with withMessage(), whether they were made before or after.
 */
class PendingMessage
{
    use Conditionable;

    private ProducerMessage $message;

    /** @var list<Closure(ProducerMessage): mixed> */
    private array $changes = [];

    private ?MessageSerializer $serializer = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly ?string $topic = null,
        private readonly bool $sync = false,
    ) {
        /** @var ProducerMessage $message */
        $message = app(ProducerMessage::class);
        $this->message = $message::create();
    }

    /** Set the topic the message is published to. */
    public function onTopic(string $topic): self
    {
        return $this->change(fn (ProducerMessage $message) => $message->onTopic($topic));
    }

    /** Set the message key. */
    public function withKey(mixed $key): self
    {
        return $this->change(fn (ProducerMessage $message) => $message->withKey($key));
    }

    /** Set the message body. */
    public function withBody(mixed $body): self
    {
        return $this->change(fn (ProducerMessage $message) => $message->withBody($body));
    }

    /** Set a key of the message body. */
    public function withBodyKey(string $key, mixed $value): self
    {
        return $this->change(fn (ProducerMessage $message) => $message->withBodyKey($key, $value));
    }

    /** Set the message headers. */
    public function withHeaders(array $headers = []): self
    {
        return $this->change(fn (ProducerMessage $message) => $message->withHeaders($headers));
    }

    /** Set a single message header. */
    public function withHeader(string $key, string|int|float $value): self
    {
        return $this->change(fn (ProducerMessage $message) => $message->withHeader($key, $value));
    }

    /** Use the given message, applying the changes made through this pending message to it. */
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

    /** Get the message that is published, with every change applied. */
    public function getMessage(): ProducerMessage
    {
        $message = clone $this->message;

        foreach ($this->changes as $change) {
            $change($message);
        }

        // The topic given to publish() is used when the message has no topic of its own.
        if (blank($message->getTopicName()) && $this->topic !== null) {
            $message->onTopic($this->topic);
        }

        return $message;
    }

    /**
     * Send the message. Asynchronous messages are queued on the connection's producer
     * and delivered in the background, synchronous ones are flushed right away.
     *
     * @throws \Junges\Kafka\Exceptions\CouldNotPublishMessage
     */
    public function send(): void
    {
        $message = $this->getMessage();

        if (blank($message->getTopicName())) {
            throw new LogicException('The message can not be published without a topic. Use the onTopic() method to set one.');
        }

        $producer = $this->connection->producer();

        $producer->produce($message, $this->serializer);

        if ($this->sync) {
            $producer->flush();
        }
    }

    private function change(Closure $change): self
    {
        $this->changes[] = $change;

        return $this;
    }
}
