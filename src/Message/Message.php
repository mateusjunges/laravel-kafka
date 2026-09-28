<?php declare(strict_types=1);

namespace Junges\Kafka\Message;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Junges\Kafka\AbstractMessage;
use Junges\Kafka\Contracts\ProducerMessage;

class Message extends AbstractMessage implements Arrayable, ProducerMessage
{
    /**
     * The message id header is set when the message is created, unless it is given, so the id stays the
     * same for the whole life of the message, including in the events dispatched while it is published.
     */
    public function __construct(
        ?string $topicName = null,
        ?int $partition = RD_KAFKA_PARTITION_UA,
        ?array $headers = [],
        mixed $body = [],
        mixed $key = null,
    ) {
        parent::__construct($topicName, $partition, $headers, $body, $key);

        $this->headers[config('kafka.message_id_key')] ??= Str::uuid()->toString();
    }

    /** Creates a new message instance.*/
    public static function create(?string $topicName = null, int $partition = RD_KAFKA_PARTITION_UA): self
    {
        return new self($topicName, $partition);
    }

    /** Set a key in the message array. */
    public function withBodyKey(string $key, mixed $value): self
    {
        $this->body[$key] = $value;

        return $this;
    }

    /** Unset a key in the message array. */
    public function forgetBodyKey(string $key): self
    {
        unset($this->body[$key]);

        return $this;
    }

    /** Set the message headers. The message id is kept, unless the given headers contain one. */
    public function withHeaders(array $headers = []): self
    {
        $idKey = config('kafka.message_id_key');

        $this->headers = [$idKey => $this->headers[$idKey], ...$headers];

        return $this;
    }

    public function onTopic(string $topic): self
    {
        $this->topicName = $topic;

        return $this;
    }

    /** Set the kafka message key. */
    public function withKey(mixed $key): self
    {
        $this->key = $key;

        return $this;
    }

    #[ArrayShape(['payload' => 'array', 'key' => 'null|string', 'headers' => 'array'])]
    public function toArray(): array
    {
        return [
            'payload' => $this->body,
            'key' => $this->key,
            'headers' => $this->headers,
        ];
    }

    public function withBody(mixed $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function withHeader(string $key, string|int|float $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }
}
