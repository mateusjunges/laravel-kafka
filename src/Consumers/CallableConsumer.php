<?php declare(strict_types=1);

namespace Junges\Kafka\Consumers;

use Closure;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use Junges\Kafka\Concerns\HandleConsumedMessage;
use Junges\Kafka\Concerns\PrepareMiddlewares;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;

class CallableConsumer extends Consumer
{
    use PrepareMiddlewares;
    use HandleConsumedMessage;

    private Dispatcher $dispatcher;

    public function __construct(private Closure|Handler $handler, private readonly array $middlewares)
    {
        $this->handler = $this->handler instanceof Handler
            ? $handler
            : $handler(...);

        $this->dispatcher = App::make(Dispatcher::class);
    }

    /** Handle the received message. */
    public function handle(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        // If the message handler should be queued, we will dispatch a job to handle this message.
        // Otherwise, the message will be handled synchronously.
        if ($this->shouldQueueHandler()) {
            $this->queueHandler($this->handler, $message, $this->middlewares);

            return;
        }

        $this->handleMessageSynchronously($message, $consumer);
    }

    private function shouldQueueHandler(): bool
    {
        return $this->handler instanceof ShouldQueue;
    }

    private function handleMessageSynchronously(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $this->handleConsumedMessage($message, $this->handler, $consumer, $this->middlewares);
    }

    /**
     * This method dispatches a job to handle the consumed message. You can customize the connection and
     * queue in which it will be dispatched using the onConnection and onQueue methods. If this
     * methods doesn't exist in the handler class, we will use the default configuration accordingly to
     * your queue.php config file.
     */
    private function queueHandler(Handler $handler, ConsumerMessage $message, array $middlewares): void
    {
        $connection = config('queue.default');

        if (method_exists($handler, 'onConnection')) {
            $connection = $handler->onConnection();
        }

        $queue = config("queue.$connection.queue", 'default');

        if (method_exists($handler, 'onQueue')) {
            $queue = $handler->onQueue();
        }

        $this->dispatcher->dispatch(
            (new DispatchQueuedHandler($handler, $message, $middlewares))
                ->onQueue($queue)
                ->onConnection($connection)
        );
    }
}
