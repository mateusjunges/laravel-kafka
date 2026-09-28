<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Illuminate\Contracts\Events\Dispatcher;
use Junges\Kafka\Concerns\ProcessesMessages;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Events\MessageSentToDLQ;
use Junges\Kafka\Events\StartedConsumingMessage;
use Junges\Kafka\MessageCounter;
use RdKafka\Message;
use Throwable;

class ConsumerFake implements Consumer
{
    use ProcessesMessages;

    private readonly MessageCounter $messageCounter;

    private bool $stopRequested = false;

    private readonly Dispatcher $dispatcher;

    /** @param ConsumerMessage[] $messages */
    public function __construct(
        private readonly Config $config,
        private readonly array $messages = [],
    ) {
        $this->messageCounter = new MessageCounter($config->getMaxMessages());
        $this->dispatcher = app(Dispatcher::class);
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

    private function handleMessage(ConsumerMessage $message): void
    {
        foreach ($this->config->getBeforeConsumingCallbacks() as $callback) {
            $callback($this);
        }

        $this->messageCounter->add();

        $this->dispatcher->dispatch(new StartedConsumingMessage($message));

        $this->processMessage($message);

        foreach ($this->config->getAfterConsumingCallbacks() as $callback) {
            $callback($this);
        }
    }

    /** Faked consumers don't publish to the dead letter queue, they only dispatch the event. */
    private function sendToDeadLetterQueue(ConsumerMessage $message, Throwable $throwable, ?Message $kafkaMessage): void
    {
        $body = $message->getBody();

        $this->dispatcher->dispatch(new MessageSentToDLQ(
            is_string($body) || $body === null ? $body : json_encode($body),
            $message->getKey(),
            $message->getHeaders(),
            $throwable,
            $message->getHeaders()[config('kafka.message_id_key')] ?? null,
        ));
    }
}
