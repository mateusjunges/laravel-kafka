<?php

namespace Junges\Kafka\Contracts;

interface KafkaMessage
{
    /**
     * Get the published message key.
     *
     * @return mixed
     */
    public function getKey(): mixed;

    /**
     * Get the topic where the message was published.
     *
     * @return string|null
     */
    public function getTopicName(): ?string;

    /**
     * Get the partition in which the message was published.
     *
     * @return int|null
     */
    public function getPartition(): ?int;

    /**
     * Get the published message headers.
     *
     * @return array|null
     */
    public function getHeaders(): ?array;

    /**
     * Get the published message body.
     *
     * @return mixed
     */
    public function getBody();
}
