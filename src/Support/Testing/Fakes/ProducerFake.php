<?php

namespace Junges\Kafka\Support\Testing\Fakes;

use Closure;
use JetBrains\PhpStorm\Pure;
use Junges\Kafka\Config\Config;
use Junges\Kafka\Message\Message;
use RdKafka\Conf;

class ProducerFake
{
    private array $messages = [];
    private Closure|null $produceCallback = null;

    public function __construct(
        private Config $config,
        private string $topic
    ) {
    }

    #[Pure]
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
            /** @var Closure $callback */
            $callback = $this->produceCallback;
            $callback($message);
        }

        return true;
    }
}
