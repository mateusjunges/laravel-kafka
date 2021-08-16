<?php

namespace Junges\Kafka\Contracts;

use Junges\Kafka\Message;

interface CanPublishMessagesToKafka
{
    public function publish(Message $message, string $topic, $key = null): self;
}