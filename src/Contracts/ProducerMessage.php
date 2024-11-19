<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface ProducerMessage extends KafkaMessage
{
    public static function create(string $topicName = null, int $partition = RD_KAFKA_PARTITION_UA): ProducerMessage;

    public function withKey(mixed $key): ProducerMessage;

    public function withBody(mixed $body): ProducerMessage;

    public function onTopic(string $topic): ProducerMessage;

    public function withHeaders(array $headers = []):  ProducerMessage;

    public function withHeader(string $key, string|int|float $value): ProducerMessage;
}
