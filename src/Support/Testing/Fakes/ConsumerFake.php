<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Illuminate\Contracts\Events\Dispatcher;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Events\MessageConsumed;
use Junges\Kafka\Events\MessageSentToDLQ;
use Junges\Kafka\Events\MessageSkipped;
use Junges\Kafka\Events\StartedConsumingMessage;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\MessageCounter;
use RdKafka\Message;
use Throwable;

class ConsumerFake implements Consumer
{
    private readonly MessageCounter $messageCounter;

    private bool $stopRequested = false;

    /** @param ConsumerMessage[] $messages */
    public function __construct(
        private readonly Config $config,
        private readonly array $messages = [],
    ) {
        $this->messageCounter = new MessageCounter($config->getMaxMessages());
    }

    /** Consume the messages given to the fake, in order. */
    public function consume(): void
    {
        $this->cancelStopConsume();
        $this->doConsume();

        $this->config->getWhenStopConsumingCallback()?->__invoke();
    }

    /** {@inheritdoc} */
    public function stopConsuming(): void
    {
        $this->stopRequested = true;
    }

    /** Will cancel the stopConsume request initiated by calling the stopConsume method */
    public function cancelStopConsume(): void
    {
        $this->stopRequested = false;
    }

    /** Count the number of messages consumed by this consumer */
    public function consumedMessagesCount(): int
    {
        return $this->messageCounter->messagesCounted();
    }

    /** {@inheritdoc} */
    public function commit(ConsumerMessage|Message|array|null $messageOrOffsets = null): void
    {
        //
    }

    /** {@inheritdoc} */
    public function commitAsync(ConsumerMessage|Message|array|null $messageOrOffsets = null): void
    {
        //
    }

    /** Get the current partition assignment for this consumer */
    public function getAssignedPartitions(): array
    {
        return [];
    }

    private function doConsume(): void
    {
        foreach ($this->messages as $message) {
            if ($this->shouldStopConsuming()) {
                break;
            }

            $this->handleMessage($message);
        }
    }

    /** Determine if the max message limit is reached. */
    private function maxMessagesLimitReached(): bool
    {
        return $this->messageCounter->maxMessagesLimitReached();
    }

    /** Return if the consumer should stop consuming messages. */
    private function shouldStopConsuming(): bool
    {
        return $this->maxMessagesLimitReached() || $this->stopRequested;
    }

    /**
     * Handle the message like the real consumer does: failed messages are retried, and then
     * sent to the dead letter queue, skipped, or stop the consumer, dispatching the same events.
     */
    private function handleMessage(ConsumerMessage $message): void
    {
        foreach ($this->config->getBeforeConsumingCallbacks() as $callback) {
            $callback($this);
        }

        $this->messageCounter->add();

        $dispatcher = app(Dispatcher::class);
        $dispatcher->dispatch(new StartedConsumingMessage($message));

        $handledMessage = null;

        try {
            retry(
                $this->config->getFailedMessageRetries() + 1,
                function (int $attempt) use ($message, &$handledMessage): void {
                    $handledMessage = $message->withAttempts($attempt);

                    $this->config->getHandler()->handle($handledMessage, $this);
                },
                $this->config->getFailedMessageRetrySleep(),
                fn () => ! $this->stopRequested,
            );

            $dispatcher->dispatch(new MessageConsumed($handledMessage));
        } catch (Throwable $throwable) {
            $this->handleFailure($handledMessage ?? $message, $throwable, $dispatcher);
        }

        foreach ($this->config->getAfterConsumingCallbacks() as $callback) {
            $callback($this);
        }
    }

    /** @throws ConsumerException */
    private function handleFailure(ConsumerMessage $message, Throwable $throwable, Dispatcher $dispatcher): void
    {
        report($throwable);

        try {
            $this->config->getHandler()->failed($message, $throwable);
        } catch (Throwable $callbackException) {
            report($callbackException);
        }

        if ($this->config->shouldSendToDlq()) {
            $body = $message->getBody();

            $dispatcher->dispatch(new MessageSentToDLQ(
                is_string($body) || $body === null ? $body : json_encode($body),
                $message->getKey(),
                $message->getHeaders(),
                $throwable,
                $message->getHeaders()[config('kafka.message_id_key')] ?? null,
            ));
        } elseif ($this->config->shouldSkipFailedMessages()) {
            $dispatcher->dispatch(new MessageSkipped($message, $throwable));
        } else {
            throw ConsumerException::stoppedOnFailure($message, $throwable);
        }
    }
}
