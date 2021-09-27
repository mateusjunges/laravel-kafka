<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Message\Message;

interface CanProduceMessages
{
    public static function create(string $broker, string $topic): self;

    public function withConfigOption(string $name, string $option): self;

    public function withConfigOptions(array $options): self;

    public function withHeaders(array $headers): self;

    public function withKafkaKey(string $key): self;

    public function withBodyKey(string $key, mixed $message): self;

    public function withMessage(Message $message): self;

    public function usingEncoder(MessageEncoder $encoder): self;

    public function withDebugEnabled(bool $enabled = true): self;

    public function getTopic(): string;

    public function send(): bool;
}
