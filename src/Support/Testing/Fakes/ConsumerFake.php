<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\MessageCounter;
use RdKafka\Conf;

class ConsumerFake
{

    private MessageCounter $messageCounter;
    private bool $stopRequested = false;
    private ?Closure $onStopConsume = null;

    /**
     * @param \Junges\Kafka\Config\Config $config
     * @param MessageDeserializer $deserializer
     * @param \Junges\Kafka\Commit\Contracts\CommitterFactory|null $committerFactory
     */
    public function __construct(private Config $config, private array $messages = [])
    {
        $this->messageCounter = new MessageCounter($config->getMaxMessages());
    }

    /**
     * Consume messages from a kafka topic in loop.
     *
     * @throws \RdKafka\Exception|\Carbon\Exceptions\Exception
     */
    public function consume(): void
    {
        foreach ($this->messages as $message) {
            if ($this->maxMessagesLimitReached() || $this->stopRequested) {
                break;
            }

            $this->handleMessage($message);
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
     * Handle the message.
     *
     * @var \Junges\Kafka\Contracts\KafkaConsumerMessage $consumer
     * @return void
     */
    private function handleMessage(KafkaConsumerMessage $message)
    {
        $this->config->getConsumer()->handle($message);
        $this->messageCounter->add();
    }
}
