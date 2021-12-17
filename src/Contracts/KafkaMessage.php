<?php

namespace Junges\Kafka\Contracts;

interface KafkaMessage
{
    public function setTopicName(string $topic): self;

    public function setPartition(int $partition): self;

    public function getKey(): mixed;

    public function getTopicName(): ?string;

    public function getPartition(): ?int;

    public function getHeaders(): ?array;

    public function getBody();
}
