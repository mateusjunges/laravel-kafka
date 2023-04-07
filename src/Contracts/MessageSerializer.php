<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface MessageSerializer
{
    public function serialize(ProducerMessage $message): ProducerMessage;
}
