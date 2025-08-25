<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\MessageCounter;
use RdKafka\Conf;
use RdKafka\Message;

class ConsumerFake implements MessageConsumer
{
    private readonly MessageCounter $messageCounter;

    /** @param ConsumerMessage[] $messages  */
    public function __construct(
        private readonly Config $config,
        private readonly array $messages = [],
        private bool $stopRequested = false,
        private ?Closure $whenStopConsuming = null
    ) {
        $this->messageCounter = new MessageCounter($config->getMaxMessages());
        $this->whenStopConsuming = $this->config->getWhenStopConsumingCallback();
    }

    /** Consume messages from a kafka topic in loop. */
    public function consume(): void
    {
        $this->doConsume();

        if ($this->shouldRunStopConsumingCallback()) {
            $callback = $this->whenStopConsuming;
            $callback(...)();
        }
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
        $this->whenStopConsuming = null;
    }

    /** Count the number of messages consumed by this consumer */
    public function consumedMessagesCount(): int
    {
        return $this->messageCounter->messagesCounted();
    }

    /** {@inheritdoc} */
    public function commit(mixed $messageOrOffsets = null): void
    {
        //
    }

    /** {@inheritdoc} */
    public function commitAsync(mixed $message_or_offsets = null): void
    {
        //
    }

    /** Get the current partition assignment for this consumer */
    public function getAssignedPartitions(): array
    {
        return [];
    }

    /** Set the consumer configuration. */
    public function setConf(array $options = []): Conf
    {
        return new Conf;
    }

    /**
     * Consume messages
     */
    public function doConsume(): void
    {
        foreach ($this->messages as $message) {
            if ($this->shouldStopConsuming()) {
                break;
            }

            $this->handleMessage($message);
        }
    }

    private function shouldRunStopConsumingCallback(): bool
    {
        return $this->whenStopConsuming !== null;
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

    /** Handle the message. */
    private function handleMessage(ConsumerMessage $message): void
    {
        $this->config->getConsumer()->handle($message, $this);
        $this->messageCounter->add();
    }

    private function getRdKafkaMessage(ConsumerMessage $message): Message
    {
        $rdKafkaMessage = new Message;
        $rdKafkaMessage->err = 0;
        $rdKafkaMessage->topic_name = $message->getTopicName();
        $rdKafkaMessage->partition = $message->getPartition();
        $rdKafkaMessage->headers = $message->getHeaders() ?? [];
        $rdKafkaMessage->payload = serialize($message->getBody());
        $rdKafkaMessage->key = $message->getKey();
        $rdKafkaMessage->offset = $message->getOffset();
        $rdKafkaMessage->timestamp = $message->getTimestamp();

        return $rdKafkaMessage;
    }

    private function getConsumerMessage(Message $message): ConsumerMessage
    {
        return app(ConsumerMessage::class, [
            'topicName' => $message->topic_name,
            'partition' => $message->partition,
            'headers' => $message->headers ?? [],
            'body' => unserialize($message->payload),
            'key' => $message->key,
            'offset' => $message->offset,
            'timestamp' => $message->timestamp,
        ]);
    }
}
