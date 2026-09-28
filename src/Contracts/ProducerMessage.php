<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface ProducerMessage extends KafkaMessage
{
    public static function create(?string $topicName = null, int $partition = RD_KAFKA_PARTITION_UA): self;

    public function withKey(mixed $key): self;

    public function withBody(mixed $body): self;

    public function onTopic(string $topic): self;

    public function withHeaders(array $headers = []): self;

    public function withHeader(string $key, string|int|float $value): self;
}
