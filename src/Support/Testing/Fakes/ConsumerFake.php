<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use RdKafka\Conf;
use RdKafka\Message;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\CanConsumeMessages;
use Junges\Kafka\MessageCounter;
use Illuminate\Support\Collection;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Contracts\HandlesBatchConfiguration;

class ConsumerFake implements CanConsumeMessages
{
    private MessageCounter $messageCounter;
    private HandlesBatchConfiguration $batchConfig;

    /**
     * @param \Junges\Kafka\Config\Config $config
     * @param \Junges\Kafka\Contracts\KafkaConsumerMessage[] $messages
     * @param bool $stopRequested
     * @param \Closure|null $onStopConsume
     */
    public function __construct(
        private Config $config,
        private array $messages = [],
        private bool $stopRequested = false,
        private ?Closure $onStopConsume = null
    ) {
        $this->messageCounter = new MessageCounter($config->getMaxMessages());
        $this->batchConfig = $this->config->getBatchConfig();
    }

    /**
     * Consume messages from a kafka topic in loop.
     *
     * @return void
     */
    public function consume(): void
    {
        if ($this->batchConfig->isBatchingEnabled()) {
            $this->batchConsume();
        } else {
            $this->defaultConsume();
        }

        if ($this->onStopConsume) {
            Closure::fromCallable($this->onStopConsume)();
        }
    }

    /**
     * Requests the consumer to stop after it's finished processing any messages to allow graceful exit
     *
     * @param Closure|null $onStop
     */
    public function stopConsume(?Closure $onStop = null): void
    {
        $this->stopRequested = true;
        $this->onStopConsume = $onStop;
    }

    /**
     * Will cancel the stopConsume request initiated by calling the stopConsume method
     */
    public function cancelStopConsume(): void
    {
        $this->stopRequested = false;
        $this->onStopConsume = null;
    }

    /**
     * Count the number of messages consumed by this consumer
     */
    public function consumedMessagesCount(): int
    {
        return $this->messageCounter->messagesCounted();
    }

    /**
     * Set the consumer configuration.
     *
     * @param array $options
     * @return \RdKafka\Conf
     */
    public function setConf(array $options = []): Conf
    {
        return new Conf();
    }

    /**
     * Determine if the max message limit is reached.
     *
     * @return bool
     */
    private function maxMessagesLimitReached(): bool
    {
        return $this->messageCounter->maxMessagesLimitReached();
    }

    /**
     * Return if the consumer should stop consuming messages.
     * @return bool
     */
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

    /**
     * Consume messages in batches
     *
     * @return void
     */
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

    /**
     * Handles batch
     *
     * @return void
     */
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

    /**
     * Tries to handle received batch of messages
     *
     * @param Collection $consumedMessages
     * @return void
     */
    private function executeBatch(Collection $collection): void
    {
        $consumedMessages = $collection
            ->map(
                fn (Message $message) =>$this->getConsumerMessage($message)
            );

        $this->config->getBatchConfig()->getConsumer()->handle($consumedMessages);
    }

    /**
     * Handle the message.
     *
     * @var \Junges\Kafka\Contracts\KafkaConsumerMessage $consumer
     * @return void
     */
    private function handleMessage(KafkaConsumerMessage $message): void
    {
        $this->config->getConsumer()->handle($message);
        $this->messageCounter->add();
    }

    private function getRdKafkaMessage(KafkaConsumerMessage $message): Message
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

    private function getConsumerMessage(Message $message): KafkaConsumerMessage
    {
        return app(KafkaConsumerMessage::class, [
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
