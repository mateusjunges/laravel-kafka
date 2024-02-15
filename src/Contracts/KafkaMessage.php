<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface KafkaMessage
{
    public function getKey(): mixed;

    public function getTopicName(): ?string;

    public function getPartition(): ?int;

    public function getHeaders(): ?array;

    public function getMessageIdentifier(): string;

    public function getBody();
}
