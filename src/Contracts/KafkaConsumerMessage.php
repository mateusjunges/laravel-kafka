<?php

namespace Junges\Kafka\Contracts;

interface KafkaConsumerMessage extends KafkaMessage
{
    /**
     * Get the offset in which message was published.
     *
     * @return int|null
     */
    public function getOffset(): ?int;

    /**
     * Get the timestamp the message was published.
     *
     * @return int|null
     */
    public function getTimestamp(): ?int;
}
