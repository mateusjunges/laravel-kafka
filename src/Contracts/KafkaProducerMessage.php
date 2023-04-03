<?php

namespace Junges\Kafka\Contracts;

interface KafkaProducerMessage extends KafkaMessage
{
    public static function create(string $topicName, int $partition): KafkaProducerMessage;

    public function withKey(?string $key): KafkaProducerMessage;

    /**
     * @param mixed $body
     */
    public function withBody($body): KafkaProducerMessage;

    public function withHeaders(array $headers = []):  KafkaProducerMessage;

    /**
     * @param mixed $value
     */
    public function withHeader(string $key, $value): KafkaProducerMessage;
}
