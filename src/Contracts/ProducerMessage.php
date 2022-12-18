<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface ProducerMessage extends KafkaMessage
{
    public static function create(string $topicName, int $partition): ProducerMessage;

    public function withKey(?string $key): ProducerMessage;

    public function withBody(mixed $body): ProducerMessage;

    public function withHeaders(array $headers = []):  ProducerMessage;

    public function withHeader(string $key, mixed $value): ProducerMessage;
}
