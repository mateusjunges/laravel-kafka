<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Consumers;

use Illuminate\Contracts\Queue\ShouldQueue;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;

final class SimpleQueueableHandler implements Handler, ShouldQueue
{
    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        //
    }
}
