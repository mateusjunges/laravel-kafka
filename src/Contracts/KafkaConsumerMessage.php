<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

/**
 * @internal
 */
interface KafkaConsumerMessage extends KafkaMessage
{
    public function getOffset(): ?int;

    public function getTimestamp(): ?int;
}
