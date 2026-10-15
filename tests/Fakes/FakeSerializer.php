<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Contracts\ProducerMessage;

final class FakeSerializer implements MessageSerializer
{
    public function serialize(ProducerMessage $message): ProducerMessage
    {
        return $message->withBody('serialized by '.self::class);
    }
}
