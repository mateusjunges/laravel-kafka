<?php declare(strict_types=1);

namespace Junges\Kafka;

use Junges\Kafka\Contracts\KafkaMessage;
use Junges\Kafka\Exceptions\MessageIdNotSet;

abstract class AbstractMessage implements KafkaMessage
{
    protected array $headers;

    public function __construct(
        protected ?string $topicName = null,
        protected ?int $partition = RD_KAFKA_PARTITION_UA,
        ?array $headers = [],
        protected mixed $body = [],
        protected mixed $key = null,
    ) {
        $this->headers = $headers ?? [];
    }

    public function getTopicName(): ?string
    {
        return $this->topicName;
    }

    public function getPartition(): ?int
    {
        return $this->partition;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getKey(): mixed
    {
        return $this->key;
    }

    /** @throws MessageIdNotSet */
    public function getMessageIdentifier(): string
    {
        $identifier = $this->getHeaders()[config('kafka.message_id_key')] ?? null;

        if (! is_string($identifier)) {
            throw new MessageIdNotSet;
        }

        return $identifier;
    }
}
