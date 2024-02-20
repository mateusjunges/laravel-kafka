<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Junges\Kafka\Concerns\HandleConsumedMessage;
use Junges\Kafka\Concerns\PrepareMiddlewares;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;

final class DispatchQueuedHandler implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use PrepareMiddlewares;
    use HandleConsumedMessage;

    public function __construct(
        public readonly Handler $handler,
        public readonly ConsumerMessage $message,
        public readonly array $middlewares = []
    ) {
    }

    public function handle(): void
    {
        // Queued handlers does not have access to an instance of the MessageConsumer class.
        $this->handleConsumedMessage(
            message: $this->message,
            handler: $this->handler,
            middlewares: $this->middlewares
        );
    }
}
