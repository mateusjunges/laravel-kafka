<?php declare(strict_types=1);

namespace Junges\Kafka\Message\Serializers;

use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\ProducerMessage;

/** Publishes messages as they are, for bodies that are already serialized. */
class NullSerializer implements MessageSerializer
{
    public function serialize(ProducerMessage $message): ProducerMessage
    {
        return $message;
    }
}
