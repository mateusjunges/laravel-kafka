<?php

namespace Junges\Kafka\Contracts;

/**
 * @internal
 */
interface KafkaMessage
{
    public function getKey(): mixed;

    public function getTopicName(): ?string;

    public function getPartition(): ?int;

    public function getHeaders(): ?array;

    public function getBody();
}
