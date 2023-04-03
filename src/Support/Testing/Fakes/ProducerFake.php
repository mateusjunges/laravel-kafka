<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\MessageBatch;
use RdKafka\Conf;

class ProducerFake
{
    /**
     * @var mixed[]
     */
    private $messages = [];
    /**
     * @var \Closure|null
     */
    private $producerCallback;
    /**
     * @var \Junges\Kafka\Config\Config
     */
    private $config;
    /**
     * @var string
     */
    private $topic;

    public function __construct(Config $config, string $topic)
    {
        $this->config = $config;
        $this->topic = $topic;
    }

    public function setConf(array $options = []): Conf
    {
        return new Conf();
    }

    public function withProduceCallback(callable $callback): self
    {
        $this->producerCallback = $callback;

        return $this;
    }

    public function produce(Message $message): bool
    {
        if ($this->producerCallback !== null) {
            $callback = $this->producerCallback;
            $callback($message);
        }

        return true;
    }

    public function produceBatch(MessageBatch $messageBatch): int
    {
        $produced = 0;
        if ($this->producerCallback !== null) {
            $callback = $this->producerCallback;

            /** @var Message $message */
            foreach ($messageBatch->getMessages() as $message) {
                $message->setTopicName($this->topic);
                $callback($message);
                $produced++;
            }
        }

        return $produced;
    }
}
