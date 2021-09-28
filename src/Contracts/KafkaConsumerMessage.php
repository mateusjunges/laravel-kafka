<?php

namespace Junges\Kafka\Contracts;

interface KafkaConsumerMessage extends KafkaMessage
{
    public function getOffset(): ?int;

    public function getTimestamp(): ?int;
}
