<?php declare(strict_types=1);

namespace Junges\Kafka\Contracts;

interface MessageDeserializer
{
    public function deserialize(ConsumerMessage $message): ConsumerMessage;
}
