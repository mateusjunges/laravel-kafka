<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageDeserializer;

final class FakeDeserializer implements MessageDeserializer
{
    public function deserialize(ConsumerMessage $message): ConsumerMessage
    {
        return $message;
    }
}
