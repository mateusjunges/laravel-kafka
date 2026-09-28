<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface ConsumerMessage extends KafkaMessage
{
    public function getOffset(): ?int;

    public function getTimestamp(): ?int;

    /** Get how many times the handler was called with this message, including the current call. */
    public function getAttempts(): int;

    /** Return a copy of this message with the given number of attempts. */
    public function withAttempts(int $attempts): static;
}
