<?php declare(strict_types=1);

namespace Junges\Kafka\Message;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Junges\Kafka\AbstractMessage;
use Junges\Kafka\Contracts\ProducerMessage;

class Message extends AbstractMessage implements Arrayable, ProducerMessage
{
    /** Creates a new message instance.*/
    #[Pure]
    public static function create(string $topicName = null, int $partition = RD_KAFKA_PARTITION_UA): ProducerMessage
    {
        return new self($topicName, $partition);
    }

    /** Set a key in the message array. */
    public function withBodyKey(string $key, mixed $message): Message
    {
        $this->body[$key] = $message;

        return $this;
    }

    /** Unset a key in the message array. */
    public function forgetBodyKey(string $key): Message
    {
        unset($this->body[$key]);

        return $this;
    }

    /** Set the message headers. */
    public function withHeaders(array $headers = []): Message
    {
        $this->headers = $headers;

        return $this;
    }

    public function onTopic(string $topic): self
    {
        $this->topicName = $topic;

        return $this;
    }

    /** Set the kafka message key. */
    public function withKey(mixed $key): Message
    {
        $this->key = $key;

        return $this;
    }

    #[ArrayShape(['payload' => "array", 'key' => "null|string", 'headers' => "array"])]
    public function toArray(): array
    {
        return [
            'payload' => $this->body,
            'key' => $this->key,
            'headers' => $this->headers,
        ];
    }

    public function withBody(mixed $body): ProducerMessage
    {
        $this->body = $body;

        return $this;
    }

    public function withHeader(string $key, string|int|float $value): ProducerMessage
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function getHeaders(): ?array
    {
        // Here we insert an uuid to be used to uniquely identify this message. If the
        // id is already set, then array_merge will override it. It's safe to do it
        // here because this class is used only when we produce a new message.
        return array_merge(parent::getHeaders(), [
            config('kafka.message_id_key') => Str::uuid()->toString(),
        ]);
    }
}
