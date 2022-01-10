<?php

namespace Junges\Kafka\Contracts;

interface KafkaProducerMessage extends KafkaMessage
{
    public static function create(string $topicName, int $partition): KafkaProducerMessage;

    public function withKey(?string $key): KafkaProducerMessage;

    public function withBody(mixed $body): KafkaProducerMessage;

    public function withHeaders(array $headers = []):  KafkaProducerMessage;

    public function withHeader(string $key, mixed $value): KafkaProducerMessage;
}
