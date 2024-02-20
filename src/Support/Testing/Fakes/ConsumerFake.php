<?php declare(strict_types=1);

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Illuminate\Support\Collection;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\MessageCounter;
use RdKafka\Conf;
use RdKafka\Message;

class ConsumerFake implements MessageConsumer
{
    private readonly MessageCounter $messageCounter;
    private readonly HandlesBatchConfiguration $batchConfig;

    /** @param \Junges\Kafka\Contracts\ConsumerMessage[] $messages  */
    public function __construct(
        private readonly Config $config,
        private readonly array $messages = [],
        private bool $stopRequested = false,
        private ?Closure $whenStopConsuming = null
    ) {
        $this->messageCounter = new MessageCounter($config->getMaxMessages());
        $this->batchConfig = $this->config->getBatchConfig();
        $this->whenStopConsuming = $this->config->getWhenStopConsumingCallback();
    }

    /** Consume messages from a kafka topic in loop. */
    public function consume(): void
    {
        if ($this->batchConfig->isBatchingEnabled()) {
            $this->batchConsume();
        } else {
            $this->defaultConsume();
        }

        if ($this->shouldRunStopConsumingCallback()) {
            $callback = $this->whenStopConsuming;
            $callback(...)();
        }
    }

    private function shouldRunStopConsumingCallback(): bool
    {
        return $this->whenStopConsuming !== null;
    }

    /** @inheritdoc */
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

    /** Set the consumer configuration. */
    public function setConf(array $options = []): Conf
    {
        return new Conf();
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
     * Consume messages
     *
     * @return void
     */
    public function defaultConsume(): void
    {
        foreach ($this->messages as $message) {
            if ($this->shouldStopConsuming()) {
                break;
            }

            $this->handleMessage($message);
        }
    }

    /** Consume messages in batches */
    public function batchConsume(): void
    {
        foreach ($this->messages as $message) {
            if ($this->shouldStopConsuming()) {
                break;
            }

            $this->messageCounter->add();
            $this->batchConfig->getBatchRepository()->push(
                $this->getRdKafkaMessage($message)
            );
            $this->handleBatch();
        }

        $this->handleIncompleteBatch();
    }

    /** Handles batch */
    private function handleBatch(): void
    {
        if ($this->batchConfig->getBatchRepository()->getBatchSize() >= $this->batchConfig->getBatchSizeLimit()) {
            $this->executeBatch($this->batchConfig->getBatchRepository()->getBatch());
            $this->batchConfig->getBatchRepository()->reset();
        }
    }

    private function handleIncompleteBatch(): void
    {
        if ($this->batchConfig->getBatchRepository()->getBatchSize() > 0) {
            $this->executeBatch($this->batchConfig->getBatchRepository()->getBatch());
            $this->batchConfig->getBatchRepository()->reset();
        }
    }

    /** Tries to handle received batch of messages. */
    private function executeBatch(Collection $collection): void
    {
        $consumedMessages = $collection
            ->map(
                fn (Message $message) => $this->getConsumerMessage($message)
            );

        $this->config->getBatchConfig()->getConsumer()->handle($consumedMessages, $this);
    }

    /** Handle the message. */
    private function handleMessage(ConsumerMessage $message): void
    {
        $this->config->getConsumer()->handle($message, $this);
        $this->messageCounter->add();
    }

    private function getRdKafkaMessage(ConsumerMessage $message): Message
    {
        $rdKafkaMessage = new Message();
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
