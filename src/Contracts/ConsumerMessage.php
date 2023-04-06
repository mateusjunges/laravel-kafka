<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface ConsumerMessage extends KafkaMessage
{
    public function getOffset(): ?int;

    public function getTimestamp(): ?int;
}
