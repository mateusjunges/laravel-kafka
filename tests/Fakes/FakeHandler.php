<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Fakes;

use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

final class FakeHandler extends Consumer
{
    private ?ConsumerMessage $lastMessage = null;

    public function lastMessage(): ?ConsumerMessage
    {
        return $this->lastMessage;
    }

    public function handle(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $this->lastMessage = $message;
    }
}
