<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;

final class FakeHandler implements Handler
{
    private ?ConsumerMessage $lastMessage = null;

    public function __invoke(ConsumerMessage $message, Consumer $consumer): void
    {
        $this->lastMessage = $message;
    }

    public function lastMessage(): ?ConsumerMessage
    {
        return $this->lastMessage;
    }
}
