<?php

namespace Junges\Kafka\Contracts;

interface KafkaMessage
{
    /**
     * @return mixed
     */
    public function getKey();

    public function getTopicName(): ?string;

    public function getPartition(): ?int;

    public function getHeaders(): ?array;

    public function getBody();
}
