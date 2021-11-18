<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Junges\Kafka\Config\Config;
use Junges\Kafka\Message\Message;
use RdKafka\Conf;

class ProducerFake
{
    private array $messages = [];
    private $produceCallback = null;

    public function __construct(
        private Config $config,
        private string $topic
    ) {
    }

    public function setConf(array $options = []): Conf
    {
        return new Conf();
    }

    public function withProduceCallback(callable $callback): self
    {
        $this->produceCallback = $callback;

        return $this;
    }

    public function produce(Message $message): bool
    {
        if ($this->produceCallback !== null) {
            $callback = $this->produceCallback;
            $callback($message);
        }

        return true;
    }
}
