<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Contracts\ConsumerMessage;

class FakeConsumer
{
    private ConsumerMessage $message;

    public function __invoke(ConsumerMessage $message)
    {
        $this->message = $message;
    }

    public function getMessage(): ConsumerMessage
    {
        return $this->message;
    }
}
