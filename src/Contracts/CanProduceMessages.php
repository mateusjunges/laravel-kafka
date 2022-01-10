<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Config\Sasl;
use Junges\Kafka\Message\Message;

interface CanProduceMessages
{
    public static function create(string $topic, string $broker = null): self;

    public function withConfigOption(string $name, string $option): self;

    public function withConfigOptions(array $options): self;

    public function withHeaders(array $headers = []): self;

    public function withKafkaKey(string $key): self;

    public function withBodyKey(string $key, mixed $message): self;

    public function withMessage(Message $message): self;

    public function withSasl(Sasl $saslConfig): self;

    public function usingSerializer(MessageSerializer $serializer): self;

    public function withDebugEnabled(bool $enabled = true): self;

    public function getTopic(): string;

    public function send(): bool;
}
